<?php

namespace App\Services;

use App\DTOs\AttendanceDTO;
use App\Enums\AdjustmentRequestStatus;
use App\Enums\AttendanceStatus;
use App\Events\AttendanceAdjusted;
use App\Events\EmployeeCheckedIn;
use App\Exceptions\BusinessException;
use App\Models\AttendanceAdjustmentRequest;
use App\Models\AttendanceRecord;
use App\Repositories\Interfaces\AttendanceRepositoryInterface;
use App\Repositories\Interfaces\ShiftRepositoryInterface;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class AttendanceService extends BaseService
{
    public function __construct(
        protected AttendanceRepositoryInterface $attendanceRepository,
        protected ShiftRepositoryInterface $shiftRepository,
    ) {
        parent::__construct($attendanceRepository);
    }

    public function checkIn(AttendanceDTO $dto): AttendanceRecord
    {
        return DB::transaction(function () use ($dto) {
            $existing = $this->attendanceRepository->findByEmployeeAndDate(
                $dto->employeeId,
                $dto->date->toDateString()
            );

            if ($existing) {
                throw new BusinessException('Employee has already checked in for today.');
            }

            $checkInTime = $dto->checkIn ?? now();
            $status = $this->determineCheckInStatus($dto->companyId, $checkInTime, $dto->shiftId);

            $record = $this->attendanceRepository->create([
                'company_id' => $dto->companyId,
                'employee_id' => $dto->employeeId,
                'date' => $dto->date->toDateString(),
                'check_in' => $checkInTime,
                'shift_id' => $dto->shiftId,
                'status' => $status->value,
                'notes' => $dto->notes,
                'ip_address' => $dto->ipAddress,
            ]);

            event(new EmployeeCheckedIn($record));

            return $record;
        });
    }

    public function checkOut(int $attendanceId, ?string $notes = null): AttendanceRecord
    {
        return DB::transaction(function () use ($attendanceId, $notes) {
            $record = $this->attendanceRepository->findOrFail($attendanceId);

            if (! $record->check_in) {
                throw new BusinessException('Cannot check out without checking in first.');
            }

            if ($record->check_out) {
                throw new BusinessException('Employee has already checked out.');
            }

            $checkOutTime = now();
            $workedHours = $this->calculateWorkedHours($record->check_in, $checkOutTime, $record->break_minutes);
            $overtimeHours = $this->calculateOvertimeHours($workedHours, $record->shift);

            $updateData = [
                'check_out' => $checkOutTime,
                'worked_hours' => $workedHours,
                'overtime_hours' => $overtimeHours,
            ];

            if ($notes) {
                $updateData['notes'] = $record->notes
                    ? $record->notes . "\n" . $notes
                    : $notes;
            }

            return $this->attendanceRepository->update($attendanceId, $updateData);
        });
    }

    public function getTodayRecords(int $companyId): Collection
    {
        $records = $this->attendanceRepository->getTodayRecords($companyId);
        $this->overlayApprovedLeaves($records->all());

        return $records;
    }

    public function paginateWithFilters(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        $page = $this->attendanceRepository->paginateWithFilters($filters, $perPage);

        // Read-time defence-in-depth: LeaveService::approveLeave already
        // writes attendance rows with status='on_leave' at approval
        // time, but that hook can miss edge cases (historical approvals
        // from before the hook existed, manual attendance edits, direct
        // DB imports). We overlay approved leaves onto the returned
        // records so the attendance list is never out of sync with the
        // leave module — but we don't persist the change, so the DB
        // remains the write-time source of truth.
        $this->overlayApprovedLeaves($page->items());

        return $page;
    }

    public function getMonthlyReport(int $employeeId, int $month, int $year): Collection
    {
        $records = $this->attendanceRepository->getMonthlyRecords($employeeId, $month, $year);
        $this->overlayApprovedLeaves($records->all());

        return $records;
    }

    /**
     * For each attendance record, check whether the employee has an
     * approved LeaveRequest that covers its date. If yes and the record
     * isn't already marked 'on_leave', mutate the in-memory status to
     * 'on_leave' so the serializer surfaces the right label.
     *
     * Runs at most one SQL query (WHERE employee_id IN + date range)
     * and does all the matching in PHP. O(n) in the number of records
     * returned, which is capped by the paginator's per-page limit.
     */
    protected function overlayApprovedLeaves(array $records): void
    {
        if (empty($records)) {
            return;
        }

        $employeeIds = [];
        $minDate = null;
        $maxDate = null;
        foreach ($records as $record) {
            if (! $record || ! $record->date) continue;
            $employeeIds[$record->employee_id] = true;
            $dateStr = $record->date instanceof Carbon
                ? $record->date->toDateString()
                : (string) $record->date;
            if ($minDate === null || $dateStr < $minDate) $minDate = $dateStr;
            if ($maxDate === null || $dateStr > $maxDate) $maxDate = $dateStr;
        }

        if (empty($employeeIds) || $minDate === null) {
            return;
        }

        // One batched query for all approved leaves that could overlap
        // any of the returned attendance rows.
        $leaves = \App\Models\LeaveRequest::query()
            ->where('status', 'approved')
            ->whereIn('employee_id', array_keys($employeeIds))
            ->whereDate('end_date', '>=', $minDate)
            ->whereDate('start_date', '<=', $maxDate)
            ->get(['employee_id', 'start_date', 'end_date']);

        if ($leaves->isEmpty()) {
            return;
        }

        // Build a quick-lookup map: "employee_id:Y-m-d" → true
        $leaveDays = [];
        foreach ($leaves as $leave) {
            $start = Carbon::parse($leave->start_date);
            $end = Carbon::parse($leave->end_date);
            for ($cursor = $start->copy(); $cursor->lte($end); $cursor->addDay()) {
                $leaveDays[$leave->employee_id . ':' . $cursor->toDateString()] = true;
            }
        }

        foreach ($records as $record) {
            if (! $record) continue;
            $dateStr = $record->date instanceof Carbon
                ? $record->date->toDateString()
                : (string) $record->date;
            $key = $record->employee_id . ':' . $dateStr;
            if (! isset($leaveDays[$key])) continue;
            if ($record->status === AttendanceStatus::OnLeave) continue;
            // Mutate in memory only — no save() — so the DB stays
            // unchanged and the resource sees the new status.
            $record->status = AttendanceStatus::OnLeave;
        }
    }

    public function requestAdjustment(array $data): AttendanceAdjustmentRequest
    {
        return DB::transaction(function () use ($data) {
            $record = $this->attendanceRepository->findOrFail($data['attendance_record_id']);

            return AttendanceAdjustmentRequest::create([
                'company_id' => $record->company_id,
                'employee_id' => $record->employee_id,
                'attendance_record_id' => $record->id,
                'requested_check_in' => $data['requested_check_in'] ?? null,
                'requested_check_out' => $data['requested_check_out'] ?? null,
                'reason' => $data['reason'],
                'status' => AdjustmentRequestStatus::Pending->value,
            ]);
        });
    }

    public function approveAdjustment(int $adjustmentId, int $reviewerId): AttendanceAdjustmentRequest
    {
        return DB::transaction(function () use ($adjustmentId, $reviewerId) {
            $adjustment = AttendanceAdjustmentRequest::findOrFail($adjustmentId);

            if ($adjustment->status !== AdjustmentRequestStatus::Pending) {
                throw new BusinessException('This adjustment request has already been processed.');
            }

            $updateData = [];

            if ($adjustment->requested_check_in) {
                $updateData['check_in'] = $adjustment->requested_check_in;
            }

            if ($adjustment->requested_check_out) {
                $updateData['check_out'] = $adjustment->requested_check_out;
            }

            if (! empty($updateData)) {
                $record = $this->attendanceRepository->findOrFail($adjustment->attendance_record_id);

                $checkIn = $updateData['check_in'] ?? $record->check_in;
                $checkOut = $updateData['check_out'] ?? $record->check_out;

                if ($checkIn && $checkOut) {
                    $updateData['worked_hours'] = $this->calculateWorkedHours(
                        Carbon::parse($checkIn),
                        Carbon::parse($checkOut),
                        $record->break_minutes
                    );
                    $updateData['overtime_hours'] = $this->calculateOvertimeHours(
                        $updateData['worked_hours'],
                        $record->shift
                    );
                }

                $this->attendanceRepository->update($adjustment->attendance_record_id, $updateData);
            }

            $adjustment->update([
                'status' => AdjustmentRequestStatus::Approved->value,
                'reviewed_by' => $reviewerId,
                'reviewed_at' => now(),
            ]);

            event(new AttendanceAdjusted($adjustment->fresh()));

            return $adjustment->fresh();
        });
    }

    public function rejectAdjustment(int $adjustmentId, int $reviewerId): AttendanceAdjustmentRequest
    {
        $adjustment = AttendanceAdjustmentRequest::findOrFail($adjustmentId);

        if ($adjustment->status !== AdjustmentRequestStatus::Pending) {
            throw new BusinessException('This adjustment request has already been processed.');
        }

        $adjustment->update([
            'status' => AdjustmentRequestStatus::Rejected->value,
            'reviewed_by' => $reviewerId,
            'reviewed_at' => now(),
        ]);

        return $adjustment->fresh();
    }

    protected function determineCheckInStatus(int $companyId, Carbon $checkInTime, ?int $shiftId = null): AttendanceStatus
    {
        $shift = $shiftId
            ? $this->shiftRepository->find($shiftId)
            : $this->shiftRepository->getDefault($companyId);

        if (! $shift) {
            return AttendanceStatus::Present;
        }

        $shiftStartTime = Carbon::parse($shift->start_time)->setDate(
            $checkInTime->year,
            $checkInTime->month,
            $checkInTime->day
        );

        $graceDeadline = $shiftStartTime->copy()->addMinutes(10);

        return $checkInTime->lte($graceDeadline)
            ? AttendanceStatus::Present
            : AttendanceStatus::Late;
    }

    protected function calculateWorkedHours(Carbon $checkIn, Carbon $checkOut, ?int $breakMinutes = null): float
    {
        $totalMinutes = $checkIn->diffInMinutes($checkOut);
        $totalMinutes -= ($breakMinutes ?? 0);

        return round(max(0, $totalMinutes / 60), 2);
    }

    protected function calculateOvertimeHours(float $workedHours, $shift): float
    {
        if (! $shift) {
            return 0;
        }

        $shiftStart = Carbon::parse($shift->start_time);
        $shiftEnd = Carbon::parse($shift->end_time);

        $standardMinutes = $shiftStart->diffInMinutes($shiftEnd) - $shift->break_duration_minutes;
        $standardHours = $standardMinutes / 60;

        $overtime = $workedHours - $standardHours;

        return round(max(0, $overtime), 2);
    }
}

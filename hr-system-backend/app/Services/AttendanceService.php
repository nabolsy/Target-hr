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

    public function getMonthlyReport(int $employeeId, int $month, int $year): Collection
    {
        return $this->attendanceRepository->getMonthlyRecords($employeeId, $month, $year);
    }

    public function getTodayRecords(int $companyId): Collection
    {
        return $this->attendanceRepository->getTodayRecords($companyId);
    }

    public function paginateWithFilters(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        return $this->attendanceRepository->paginateWithFilters($filters, $perPage);
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

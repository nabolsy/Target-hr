<?php

namespace App\Services;

use App\DTOs\LeaveRequestDTO;
use App\Enums\LeaveRequestStatus;
use App\Events\LeaveApproved;
use App\Events\LeaveRejected;
use App\Events\LeaveRequested;
use App\Exceptions\BusinessException;
use App\Models\LeaveRequest;
use App\Repositories\Interfaces\HolidayRepositoryInterface;
use App\Repositories\Interfaces\LeaveBalanceRepositoryInterface;
use App\Repositories\Interfaces\LeaveRequestRepositoryInterface;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class LeaveService extends BaseService
{
    public function __construct(
        protected LeaveRequestRepositoryInterface $leaveRequestRepository,
        protected LeaveBalanceRepositoryInterface $leaveBalanceRepository,
        protected HolidayRepositoryInterface $holidayRepository,
    ) {
        parent::__construct($leaveRequestRepository);
    }

    /**
     * Apply for leave: validate balance, check conflicts, calculate working days.
     */
    public function applyForLeave(LeaveRequestDTO $dto): LeaveRequest
    {
        return DB::transaction(function () use ($dto) {
            // Guard half-day requests against leave types that don't
            // allow them. We do this BEFORE touching the balance so
            // the caller gets a clear validation error instead of a
            // cryptic balance failure.
            if ($dto->isHalfDay) {
                $leaveType = \App\Models\LeaveType::find($dto->leaveTypeId);
                if ($leaveType && ! $leaveType->allows_half_day) {
                    throw new BusinessException(
                        "Half-day leave is not allowed for {$leaveType->name}."
                    );
                }
            }

            // Half-day requests must be a single date — if the client
            // sent a range we silently collapse it to the start so the
            // server is the source of truth for the invariant.
            $effectiveEndDate = $dto->isHalfDay ? $dto->startDate : $dto->endDate;

            $startDate = Carbon::parse($dto->startDate);
            $endDate = Carbon::parse($effectiveEndDate);

            if ($endDate->lt($startDate)) {
                throw new BusinessException('End date must be on or after start date.');
            }

            // Check for conflicting leave requests
            $conflicts = $this->leaveRequestRepository->getConflicting(
                $dto->employeeId,
                $dto->startDate,
                $effectiveEndDate
            );

            if ($conflicts->isNotEmpty()) {
                throw new BusinessException('You already have a leave request that overlaps with the selected dates.');
            }

            // Calculate working days
            $companyId = auth()->user()->company_id;
            $totalDays = $dto->isHalfDay
                ? 0.5
                : $this->calcWorkingDays($companyId, $startDate, $endDate);

            if ($totalDays <= 0) {
                throw new BusinessException('The selected date range contains no working days.');
            }

            // Validate leave balance
            $year = $startDate->year;
            $balance = $this->leaveBalanceRepository->getByEmployeeTypeAndYear(
                $dto->employeeId,
                $dto->leaveTypeId,
                $year
            );

            if (! $balance) {
                throw new BusinessException('No leave balance found for the selected leave type and year.');
            }

            if ((float) $balance->remaining_days < $totalDays) {
                throw new BusinessException(
                    "Insufficient leave balance. Available: {$balance->remaining_days} days, Requested: {$totalDays} days."
                );
            }

            // Create the leave request
            $leaveRequest = $this->leaveRequestRepository->create([
                'company_id' => $companyId,
                'employee_id' => $dto->employeeId,
                'leave_type_id' => $dto->leaveTypeId,
                'start_date' => $dto->startDate,
                'end_date' => $effectiveEndDate,
                'is_half_day' => $dto->isHalfDay,
                'duration_type' => $dto->durationType,
                'total_days' => $totalDays,
                'reason' => $dto->reason,
                'attachment_path' => $dto->attachmentPath,
                'status' => LeaveRequestStatus::Pending->value,
            ]);

            event(new LeaveRequested($leaveRequest));

            return $leaveRequest;
        });
    }

    /**
     * Approve leave: update status, deduct balance, create attendance records.
     */
    public function approveLeave(int $leaveRequestId): LeaveRequest
    {
        return DB::transaction(function () use ($leaveRequestId) {
            $leaveRequest = $this->leaveRequestRepository->findOrFail($leaveRequestId);

            if ($leaveRequest->status !== LeaveRequestStatus::Pending) {
                throw new BusinessException('Only pending leave requests can be approved.');
            }

            // Update leave request status
            $leaveRequest = $this->leaveRequestRepository->update($leaveRequestId, [
                'status' => LeaveRequestStatus::Approved->value,
                'approved_by' => auth()->id(),
                'approved_at' => now(),
            ]);

            // Deduct from leave balance
            $year = Carbon::parse($leaveRequest->start_date)->year;
            $balance = $this->leaveBalanceRepository->getByEmployeeTypeAndYear(
                $leaveRequest->employee_id,
                $leaveRequest->leave_type_id,
                $year
            );

            if ($balance) {
                $newUsed = (float) $balance->used_days + (float) $leaveRequest->total_days;
                $newRemaining = (float) $balance->total_days - $newUsed;

                $this->leaveBalanceRepository->update($balance->id, [
                    'used_days' => $newUsed,
                    'remaining_days' => $newRemaining,
                ]);
            }

            // Create attendance records with 'on_leave' status for each leave day
            $this->createLeaveAttendanceRecords($leaveRequest);

            event(new LeaveApproved($leaveRequest));

            return $leaveRequest;
        });
    }

    /**
     * Reject a leave request.
     */
    public function rejectLeave(int $leaveRequestId, string $rejectionReason): LeaveRequest
    {
        return DB::transaction(function () use ($leaveRequestId, $rejectionReason) {
            $leaveRequest = $this->leaveRequestRepository->findOrFail($leaveRequestId);

            if ($leaveRequest->status !== LeaveRequestStatus::Pending) {
                throw new BusinessException('Only pending leave requests can be rejected.');
            }

            $leaveRequest = $this->leaveRequestRepository->update($leaveRequestId, [
                'status' => LeaveRequestStatus::Rejected->value,
                'approved_by' => auth()->id(),
                'approved_at' => now(),
                'rejection_reason' => $rejectionReason,
            ]);

            event(new LeaveRejected($leaveRequest));

            return $leaveRequest;
        });
    }

    /**
     * Cancel a leave request. If already approved, restore balance.
     */
    public function cancelLeave(int $leaveRequestId): LeaveRequest
    {
        return DB::transaction(function () use ($leaveRequestId) {
            $leaveRequest = $this->leaveRequestRepository->findOrFail($leaveRequestId);

            if (! in_array($leaveRequest->status, [LeaveRequestStatus::Pending, LeaveRequestStatus::Approved])) {
                throw new BusinessException('Only pending or approved leave requests can be cancelled.');
            }

            // If approved, restore balance
            if ($leaveRequest->status === LeaveRequestStatus::Approved) {
                $year = Carbon::parse($leaveRequest->start_date)->year;
                $balance = $this->leaveBalanceRepository->getByEmployeeTypeAndYear(
                    $leaveRequest->employee_id,
                    $leaveRequest->leave_type_id,
                    $year
                );

                if ($balance) {
                    $newUsed = max(0, (float) $balance->used_days - (float) $leaveRequest->total_days);
                    $newRemaining = (float) $balance->total_days - $newUsed;

                    $this->leaveBalanceRepository->update($balance->id, [
                        'used_days' => $newUsed,
                        'remaining_days' => $newRemaining,
                    ]);
                }

                // Remove attendance records for cancelled leave
                DB::table('attendance_records')
                    ->where('employee_id', $leaveRequest->employee_id)
                    ->where('status', 'on_leave')
                    ->whereBetween('date', [
                        $leaveRequest->start_date->format('Y-m-d'),
                        $leaveRequest->end_date->format('Y-m-d'),
                    ])
                    ->delete();
            }

            $leaveRequest = $this->leaveRequestRepository->update($leaveRequestId, [
                'status' => LeaveRequestStatus::Cancelled->value,
            ]);

            return $leaveRequest;
        });
    }

    /**
     * Get leave balance for an employee for a given year.
     */
    public function getBalance(int $employeeId, int $year): Collection
    {
        return $this->leaveBalanceRepository->getByEmployeeAndYear($employeeId, $year);
    }

    /**
     * Allocate or update leave balance for an employee.
     */
    public function allocateBalance(
        int $employeeId,
        int $leaveTypeId,
        int $year,
        float $totalDays,
        ?int $companyId = null
    ): \Illuminate\Database\Eloquent\Model {
        $companyId = $companyId ?? auth()->user()->company_id;

        $existing = $this->leaveBalanceRepository->getByEmployeeTypeAndYear(
            $employeeId,
            $leaveTypeId,
            $year
        );

        if ($existing) {
            $newRemaining = $totalDays - (float) $existing->used_days;

            return $this->leaveBalanceRepository->update($existing->id, [
                'total_days' => $totalDays,
                'remaining_days' => $newRemaining,
            ]);
        }

        return $this->leaveBalanceRepository->create([
            'company_id' => $companyId,
            'employee_id' => $employeeId,
            'leave_type_id' => $leaveTypeId,
            'year' => $year,
            'total_days' => $totalDays,
            'used_days' => 0,
            'remaining_days' => $totalDays,
        ]);
    }

    /**
     * Paginate leave requests with filters.
     */
    public function paginateWithFilters(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        return $this->leaveRequestRepository->paginateWithFilters($filters, $perPage);
    }

    /**
     * Calculate working days between two dates, excluding weekends and company holidays.
     */
    public function calcWorkingDays(int $companyId, Carbon $startDate, Carbon $endDate): float
    {
        $workingDays = 0;

        $period = CarbonPeriod::create($startDate, $endDate);

        foreach ($period as $date) {
            // Skip weekends (Saturday = 6, Sunday = 0)
            if ($date->isWeekend()) {
                continue;
            }

            // Skip company holidays
            if ($this->holidayRepository->isHoliday($companyId, $date->format('Y-m-d'))) {
                continue;
            }

            $workingDays++;
        }

        return (float) $workingDays;
    }

    /**
     * Create attendance records with 'on_leave' status for each working day within the leave period.
     */
    protected function createLeaveAttendanceRecords(LeaveRequest $leaveRequest): void
    {
        $period = CarbonPeriod::create($leaveRequest->start_date, $leaveRequest->end_date);
        $companyId = $leaveRequest->company_id;

        foreach ($period as $date) {
            if ($date->isWeekend()) {
                continue;
            }

            if ($this->holidayRepository->isHoliday($companyId, $date->format('Y-m-d'))) {
                continue;
            }

            DB::table('attendance_records')->updateOrInsert(
                [
                    'employee_id' => $leaveRequest->employee_id,
                    'date' => $date->format('Y-m-d'),
                ],
                [
                    'company_id' => $companyId,
                    'employee_id' => $leaveRequest->employee_id,
                    'date' => $date->format('Y-m-d'),
                    'status' => 'on_leave',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }
}

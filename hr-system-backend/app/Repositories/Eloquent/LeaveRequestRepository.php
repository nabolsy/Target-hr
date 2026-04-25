<?php

namespace App\Repositories\Eloquent;

use App\Enums\LeaveRequestStatus;
use App\Models\LeaveRequest;
use App\Repositories\Concerns\AppliesAccessScope;
use App\Repositories\Interfaces\LeaveRequestRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class LeaveRequestRepository extends BaseRepository implements LeaveRequestRepositoryInterface
{
    use AppliesAccessScope;

    public function __construct(LeaveRequest $model)
    {
        parent::__construct($model);
    }

    public function getByEmployee(int $employeeId): Collection
    {
        return $this->model
            ->where('employee_id', $employeeId)
            ->with(['leaveType', 'approver'])
            ->orderByDesc('created_at')
            ->get();
    }

    public function getPending(): Collection
    {
        return $this->model
            ->where('status', LeaveRequestStatus::Pending)
            ->with(['employee', 'leaveType'])
            ->orderBy('created_at')
            ->get();
    }

    public function paginateWithFilters(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->model->with(['employee', 'leaveType', 'approver']);

        // Access scope (employee subset filter from PermissionService).
        $this->applyAccessScope($query, $filters);

        if (! empty($filters['employee_id'])) {
            $query->where('employee_id', $filters['employee_id']);
        }

        if (! empty($filters['leave_type_id'])) {
            $query->where('leave_type_id', $filters['leave_type_id']);
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['start_date'])) {
            $query->where('start_date', '>=', $filters['start_date']);
        }

        if (! empty($filters['end_date'])) {
            $query->where('end_date', '<=', $filters['end_date']);
        }

        if (! empty($filters['year'])) {
            $query->whereYear('start_date', $filters['year']);
        }

        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortDir = $filters['sort_dir'] ?? 'desc';
        $query->orderBy($sortBy, $sortDir);

        return $query->paginate($perPage);
    }

    public function getConflicting(int $employeeId, string $startDate, string $endDate): Collection
    {
        return $this->model
            ->where('employee_id', $employeeId)
            ->whereIn('status', [LeaveRequestStatus::Pending, LeaveRequestStatus::Approved])
            ->where(function ($query) use ($startDate, $endDate) {
                $query->whereBetween('start_date', [$startDate, $endDate])
                    ->orWhereBetween('end_date', [$startDate, $endDate])
                    ->orWhere(function ($q) use ($startDate, $endDate) {
                        $q->where('start_date', '<=', $startDate)
                            ->where('end_date', '>=', $endDate);
                    });
            })
            ->get();
    }
}

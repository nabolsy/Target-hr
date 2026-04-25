<?php

namespace App\Repositories\Eloquent;

use App\Models\AttendanceRecord;
use App\Repositories\Concerns\AppliesAccessScope;
use App\Repositories\Interfaces\AttendanceRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class AttendanceRepository extends BaseRepository implements AttendanceRepositoryInterface
{
    use AppliesAccessScope;

    public function __construct(AttendanceRecord $model)
    {
        parent::__construct($model);
    }

    public function findByEmployeeAndDate(int $employeeId, string $date): ?AttendanceRecord
    {
        return $this->model
            ->where('employee_id', $employeeId)
            ->whereDate('date', $date)
            ->first();
    }

    public function getMonthlyRecords(int $employeeId, int $month, int $year): Collection
    {
        return $this->model
            ->forEmployee($employeeId)
            ->forMonth($month, $year)
            ->orderBy('date')
            ->get();
    }

    public function getTodayRecords(int $companyId): Collection
    {
        return $this->model
            ->where('company_id', $companyId)
            ->forDate(now()->toDateString())
            ->with(['employee', 'shift'])
            ->get();
    }

    public function paginateWithFilters(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->model->query()->with(['employee', 'shift']);

        // Access scope (employee subset filter from PermissionService).
        $this->applyAccessScope($query, $filters);

        if (! empty($filters['employee_id'])) {
            $query->where('employee_id', $filters['employee_id']);
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['date'])) {
            $query->whereDate('date', $filters['date']);
        }

        if (! empty($filters['date_from'])) {
            $query->whereDate('date', '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $query->whereDate('date', '<=', $filters['date_to']);
        }

        if (! empty($filters['shift_id'])) {
            $query->where('shift_id', $filters['shift_id']);
        }

        $sortBy = $filters['sort_by'] ?? 'date';
        $sortDir = $filters['sort_dir'] ?? 'desc';
        $query->orderBy($sortBy, $sortDir);

        return $query->paginate($perPage);
    }
}

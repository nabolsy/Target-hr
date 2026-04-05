<?php

namespace App\Repositories\Eloquent;

use App\Models\PayrollRecord;
use App\Repositories\Interfaces\PayrollRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class PayrollRepository extends BaseRepository implements PayrollRepositoryInterface
{
    public function __construct(PayrollRecord $model)
    {
        parent::__construct($model);
    }

    public function getByPeriod(int $periodId): Collection
    {
        return $this->model
            ->where('payroll_period_id', $periodId)
            ->with('employee')
            ->get();
    }

    public function getByEmployee(int $employeeId): Collection
    {
        return $this->model
            ->where('employee_id', $employeeId)
            ->with('payrollPeriod')
            ->orderByDesc('created_at')
            ->get();
    }

    public function paginateWithFilters(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->model->query()->with(['employee', 'payrollPeriod']);

        if (! empty($filters['payroll_period_id'])) {
            $query->where('payroll_period_id', $filters['payroll_period_id']);
        }

        if (! empty($filters['employee_id'])) {
            $query->where('employee_id', $filters['employee_id']);
        }

        if (! empty($filters['company_id'])) {
            $query->where('company_id', $filters['company_id']);
        }

        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortDir = $filters['sort_dir'] ?? 'desc';

        $allowedSorts = ['created_at', 'basic_salary', 'gross_salary', 'net_salary'];

        if (in_array($sortBy, $allowedSorts)) {
            $query->orderBy($sortBy, $sortDir);
        } else {
            $query->orderBy('created_at', 'desc');
        }

        return $query->paginate($perPage);
    }
}

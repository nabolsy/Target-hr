<?php

namespace App\Repositories\Eloquent;

use App\Models\Employee;
use App\Repositories\Interfaces\EmployeeRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class EmployeeRepository extends BaseRepository implements EmployeeRepositoryInterface
{
    public function __construct(Employee $model)
    {
        parent::__construct($model);
    }

    public function getByCompany(int $companyId): Collection
    {
        return $this->model->where('company_id', $companyId)->get();
    }

    public function getByDepartment(int $departmentId): Collection
    {
        return $this->model->where('department_id', $departmentId)->get();
    }

    public function getByManager(int $managerId): Collection
    {
        return $this->model->where('manager_id', $managerId)->get();
    }

    public function paginateWithFilters(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->model->query();

        if (! empty($filters['company_id'])) {
            $query->where('company_id', $filters['company_id']);
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['department_id'])) {
            $query->where('department_id', $filters['department_id']);
        }

        if (! empty($filters['designation_id'])) {
            $query->where('designation_id', $filters['designation_id']);
        }

        if (! empty($filters['employment_type'])) {
            $query->where('employment_type', $filters['employment_type']);
        }

        if (! empty($filters['manager_id'])) {
            $query->where('manager_id', $filters['manager_id']);
        }

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('employee_id_number', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortDir = $filters['sort_dir'] ?? 'desc';

        $allowedSorts = [
            'created_at', 'first_name', 'last_name', 'email',
            'employee_id_number', 'join_date', 'status', 'employment_type',
        ];

        if (in_array($sortBy, $allowedSorts)) {
            $query->orderBy($sortBy, $sortDir);
        } else {
            $query->orderBy('created_at', 'desc');
        }

        return $query->paginate($perPage);
    }

    public function findByEmployeeIdNumber(string $employeeIdNumber, int $companyId): ?Employee
    {
        return $this->model
            ->where('employee_id_number', $employeeIdNumber)
            ->where('company_id', $companyId)
            ->first();
    }

    public function countByCompany(int $companyId): int
    {
        return $this->model->where('company_id', $companyId)->count();
    }
}

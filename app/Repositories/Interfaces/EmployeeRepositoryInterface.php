<?php

namespace App\Repositories\Interfaces;

use App\Models\Employee;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

interface EmployeeRepositoryInterface extends BaseRepositoryInterface
{
    public function getByCompany(int $companyId): Collection;

    public function getByDepartment(int $departmentId): Collection;

    public function getByManager(int $managerId): Collection;

    public function paginateWithFilters(array $filters, int $perPage = 15): LengthAwarePaginator;

    public function findByEmployeeIdNumber(string $employeeIdNumber, int $companyId): ?Employee;

    public function countByCompany(int $companyId): int;
}

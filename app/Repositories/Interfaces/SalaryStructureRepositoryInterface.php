<?php

namespace App\Repositories\Interfaces;

use Illuminate\Database\Eloquent\Collection;

interface SalaryStructureRepositoryInterface extends BaseRepositoryInterface
{
    public function getByEmployee(int $employeeId): ?object;

    public function getByCompany(int $companyId): Collection;
}

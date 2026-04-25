<?php

namespace App\Repositories\Interfaces;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

interface PayrollRepositoryInterface extends BaseRepositoryInterface
{
    public function getByPeriod(int $periodId): Collection;

    public function getByEmployee(int $employeeId): Collection;

    public function paginateWithFilters(array $filters, int $perPage = 15): LengthAwarePaginator;
}

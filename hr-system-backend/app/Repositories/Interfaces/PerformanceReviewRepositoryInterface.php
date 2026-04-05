<?php

namespace App\Repositories\Interfaces;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

interface PerformanceReviewRepositoryInterface extends BaseRepositoryInterface
{
    public function getByEmployee(int $employeeId): Collection;

    public function getByCycle(int $reviewCycleId): Collection;

    public function paginateWithFilters(array $filters, int $perPage = 15): LengthAwarePaginator;
}

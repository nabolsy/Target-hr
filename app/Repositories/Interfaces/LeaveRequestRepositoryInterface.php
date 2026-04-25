<?php

namespace App\Repositories\Interfaces;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

interface LeaveRequestRepositoryInterface extends BaseRepositoryInterface
{
    public function getByEmployee(int $employeeId): Collection;

    public function getPending(): Collection;

    public function paginateWithFilters(array $filters, int $perPage = 15): LengthAwarePaginator;

    public function getConflicting(int $employeeId, string $startDate, string $endDate): Collection;
}

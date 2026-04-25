<?php

namespace App\Repositories\Interfaces;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

interface OnboardingChecklistRepositoryInterface extends BaseRepositoryInterface
{
    public function getByEmployee(int $employeeId): Collection;

    public function getPending(int $companyId): Collection;

    public function paginateWithFilters(array $filters, int $perPage = 15): LengthAwarePaginator;
}

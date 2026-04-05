<?php

namespace App\Repositories\Interfaces;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

interface JobOpeningRepositoryInterface extends BaseRepositoryInterface
{
    public function getByCompany(int $companyId): Collection;

    public function getOpen(int $companyId): Collection;

    public function paginateWithFilters(array $filters, int $perPage = 15): LengthAwarePaginator;
}

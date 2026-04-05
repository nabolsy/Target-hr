<?php

namespace App\Repositories\Interfaces;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

interface AssetRepositoryInterface extends BaseRepositoryInterface
{
    public function getByCompany(int $companyId): Collection;

    public function getAvailable(int $companyId): Collection;

    public function getAssigned(int $companyId): Collection;

    public function getByEmployee(int $employeeId): Collection;

    public function paginateWithFilters(array $filters, int $perPage = 15): LengthAwarePaginator;

    public function getByCategory(string $category, int $companyId): Collection;
}

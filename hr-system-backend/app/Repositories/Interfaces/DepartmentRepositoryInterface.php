<?php

namespace App\Repositories\Interfaces;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

interface DepartmentRepositoryInterface extends BaseRepositoryInterface
{
    public function getByCompany(int $companyId): Collection;

    public function getActiveByCompany(int $companyId): Collection;

    public function getRootDepartments(int $companyId): Collection;

    public function getSubDepartments(int $parentId): Collection;

    public function paginateWithFilters(array $filters, int $perPage = 15): LengthAwarePaginator;
}

<?php

namespace App\Repositories\Interfaces;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

interface AuditLogRepositoryInterface extends BaseRepositoryInterface
{
    public function getByModel(string $type, int $id): Collection;

    public function getByUser(int $userId): Collection;

    public function paginateWithFilters(array $filters, int $perPage = 15): LengthAwarePaginator;

    public function getRecent(int $companyId, int $limit = 50): Collection;
}

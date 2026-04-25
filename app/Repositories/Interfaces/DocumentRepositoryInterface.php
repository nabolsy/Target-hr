<?php

namespace App\Repositories\Interfaces;

use App\Enums\DocumentType;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

interface DocumentRepositoryInterface extends BaseRepositoryInterface
{
    public function getByEmployee(int $employeeId): Collection;

    public function getExpiring(int $days = 30): Collection;

    public function getExpired(): Collection;

    public function paginateWithFilters(array $filters, int $perPage = 15): LengthAwarePaginator;

    public function getByType(DocumentType $type): Collection;
}

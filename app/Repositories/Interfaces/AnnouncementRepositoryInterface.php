<?php

namespace App\Repositories\Interfaces;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

interface AnnouncementRepositoryInterface extends BaseRepositoryInterface
{
    public function getPublished(): Collection;

    public function getForEmployee(int $employeeId, ?int $departmentId): Collection;

    public function paginateWithFilters(array $filters, int $perPage = 15): LengthAwarePaginator;

    public function getUnreadCount(int $userId, ?int $departmentId = null): int;
}

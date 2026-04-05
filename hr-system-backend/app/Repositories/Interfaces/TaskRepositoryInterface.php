<?php

namespace App\Repositories\Interfaces;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;

interface TaskRepositoryInterface extends BaseRepositoryInterface
{
    public function getByBoard(int $boardId): Collection;

    public function getByColumn(int $columnId): Collection;

    public function getByAssignee(int $employeeId): Collection;

    public function getOverdue(int $companyId): Collection;

    public function paginateWithFilters(array $filters, int $perPage = 15): LengthAwarePaginator;

    public function moveTask(int $taskId, int $columnId, int $sortOrder): Model;
}

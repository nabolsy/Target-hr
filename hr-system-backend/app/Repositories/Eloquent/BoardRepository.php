<?php

namespace App\Repositories\Eloquent;

use App\Models\Board;
use App\Repositories\Interfaces\BoardRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class BoardRepository extends BaseRepository implements BoardRepositoryInterface
{
    public function __construct(Board $model)
    {
        parent::__construct($model);
    }

    public function getByCompany(int $companyId): Collection
    {
        return $this->model
            ->where('company_id', $companyId)
            ->active()
            ->withCount('tasks')
            ->orderByDesc('created_at')
            ->get();
    }

    public function getByDepartment(int $departmentId): Collection
    {
        return $this->model
            ->where('department_id', $departmentId)
            ->active()
            ->withCount('tasks')
            ->orderByDesc('created_at')
            ->get();
    }

    public function getWithColumns(int $boardId): Model
    {
        return $this->model
            ->with(['columns.tasks' => function ($query) {
                $query->orderBy('sort_order');
            }, 'columns.tasks.assignees', 'columns.tasks.labels'])
            ->withCount('tasks')
            ->findOrFail($boardId);
    }
}

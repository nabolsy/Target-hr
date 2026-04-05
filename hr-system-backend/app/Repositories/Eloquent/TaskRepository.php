<?php

namespace App\Repositories\Eloquent;

use App\Models\Task;
use App\Repositories\Interfaces\TaskRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;

class TaskRepository extends BaseRepository implements TaskRepositoryInterface
{
    public function __construct(Task $model)
    {
        parent::__construct($model);
    }

    public function getByBoard(int $boardId): Collection
    {
        return $this->model
            ->where('board_id', $boardId)
            ->with(['assignees', 'labels', 'column'])
            ->orderBy('sort_order')
            ->get();
    }

    public function getByColumn(int $columnId): Collection
    {
        return $this->model
            ->where('column_id', $columnId)
            ->with(['assignees', 'labels'])
            ->orderBy('sort_order')
            ->get();
    }

    public function getByAssignee(int $employeeId): Collection
    {
        return $this->model
            ->byAssignee($employeeId)
            ->with(['board', 'column', 'assignees', 'labels'])
            ->orderByDesc('created_at')
            ->get();
    }

    public function getOverdue(int $companyId): Collection
    {
        return $this->model
            ->where('company_id', $companyId)
            ->overdue()
            ->with(['board', 'column', 'assignees'])
            ->orderBy('due_date')
            ->get();
    }

    public function paginateWithFilters(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->model->with(['board', 'column', 'assignees', 'labels', 'creator']);

        if (! empty($filters['board_id'])) {
            $query->where('board_id', $filters['board_id']);
        }

        if (! empty($filters['column_id'])) {
            $query->where('column_id', $filters['column_id']);
        }

        if (! empty($filters['company_id'])) {
            $query->where('company_id', $filters['company_id']);
        }

        if (! empty($filters['priority'])) {
            $query->where('priority', $filters['priority']);
        }

        if (! empty($filters['assignee_id'])) {
            $query->byAssignee($filters['assignee_id']);
        }

        if (! empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('title', 'like', "%{$filters['search']}%")
                    ->orWhere('description', 'like', "%{$filters['search']}%");
            });
        }

        if (isset($filters['is_archived'])) {
            $query->where('is_archived', $filters['is_archived']);
        }

        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortDir = $filters['sort_dir'] ?? 'desc';
        $query->orderBy($sortBy, $sortDir);

        return $query->paginate($perPage);
    }

    public function moveTask(int $taskId, int $columnId, int $sortOrder): Model
    {
        $task = $this->findOrFail($taskId);

        // Reorder tasks in the target column: shift tasks at or after the target position down
        $this->model
            ->where('column_id', $columnId)
            ->where('sort_order', '>=', $sortOrder)
            ->where('id', '!=', $taskId)
            ->increment('sort_order');

        $task->update([
            'column_id' => $columnId,
            'sort_order' => $sortOrder,
        ]);

        return $task->fresh(['column', 'assignees', 'labels']);
    }
}

<?php

namespace App\Services;

use App\DTOs\TaskDTO;
use App\Events\TaskAssigned;
use App\Events\TaskMoved;
use App\Exceptions\BusinessException;
use App\Models\BoardColumn;
use App\Models\Employee;
use App\Models\Task;
use App\Models\TaskActivityLog;
use App\Models\TaskAttachment;
use App\Models\TaskChecklistItem;
use App\Models\TaskComment;
use App\Repositories\Interfaces\TaskRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class TaskService extends BaseService
{
    public function __construct(
        protected TaskRepositoryInterface $taskRepository,
    ) {
        parent::__construct($taskRepository);
    }

    public function createTask(TaskDTO $dto): Task
    {
        return DB::transaction(function () use ($dto) {
            $data = $dto->toArray();
            $data['company_id'] = $data['company_id'] ?? auth()->user()->company_id;
            $data['creator_id'] = $data['creator_id'] ?? auth()->id();

            // If no column_id provided, assign to the first column of the board
            if (empty($data['column_id']) && ! empty($data['board_id'])) {
                $firstColumn = BoardColumn::where('board_id', $data['board_id'])
                    ->orderBy('sort_order')
                    ->first();

                if ($firstColumn) {
                    $data['column_id'] = $firstColumn->id;
                }
            }

            // Set sort_order to end of column if not specified
            if (! isset($data['sort_order']) && ! empty($data['column_id'])) {
                $maxSort = Task::where('column_id', $data['column_id'])->max('sort_order');
                $data['sort_order'] = ($maxSort ?? -1) + 1;
            }

            $task = $this->taskRepository->create($data);

            // Assign assignees if provided. We fire one TaskAssigned per
            // assignee so the listener can notify each user — bulk
            // create previously fired no event, leaving brand-new
            // assignees with no notification.
            if (! empty($dto->assigneeIds)) {
                $task->assignees()->attach($dto->assigneeIds, [
                    'assigned_by' => auth()->id(),
                    'assigned_at' => now(),
                ]);
                $newAssignees = Employee::whereIn('id', $dto->assigneeIds)->get();
                foreach ($newAssignees as $emp) {
                    event(new TaskAssigned($task, $emp));
                }
            }

            // Attach labels if provided
            if (! empty($dto->labelIds)) {
                $task->labels()->attach($dto->labelIds);
            }

            return $task->load(['assignees', 'labels', 'column', 'creator']);
        });
    }

    public function updateTask(int $taskId, TaskDTO $dto): Model
    {
        return DB::transaction(function () use ($taskId, $dto) {
            $task = $this->taskRepository->update($taskId, $dto->toArray());

            if ($dto->assigneeIds !== null) {
                // Capture who's already on the task BEFORE sync so we
                // only notify the diff — not every existing assignee
                // each time someone edits the description.
                $before = $task->assignees()->pluck('employees.id')->all();
                $task->assignees()->sync(
                    collect($dto->assigneeIds)->mapWithKeys(fn ($id) => [
                        $id => ['assigned_by' => auth()->id(), 'assigned_at' => now()],
                    ])->all()
                );
                $newlyAdded = array_values(array_diff($dto->assigneeIds, $before));
                if (! empty($newlyAdded)) {
                    $newAssignees = Employee::whereIn('id', $newlyAdded)->get();
                    foreach ($newAssignees as $emp) {
                        event(new TaskAssigned($task, $emp));
                    }
                }
            }

            if ($dto->labelIds !== null) {
                $task->labels()->sync($dto->labelIds);
            }

            return $task->load(['assignees', 'labels', 'column', 'creator']);
        });
    }

    public function moveTask(int $taskId, int $columnId, int $sortOrder): Model
    {
        return DB::transaction(function () use ($taskId, $columnId, $sortOrder) {
            $task = $this->taskRepository->findOrFail($taskId);
            $oldColumn = $task->column;
            $newColumn = BoardColumn::findOrFail($columnId);

            $task = $this->taskRepository->moveTask($taskId, $columnId, $sortOrder);

            // Log activity
            TaskActivityLog::create([
                'task_id' => $taskId,
                'user_id' => auth()->id(),
                'action' => 'moved',
                'description' => "Moved task from \"{$oldColumn->name}\" to \"{$newColumn->name}\"",
                'old_value' => $oldColumn->name,
                'new_value' => $newColumn->name,
            ]);

            event(new TaskMoved($task, $oldColumn, $newColumn, auth()->user()));

            return $task;
        });
    }

    public function assignTask(int $taskId, int $employeeId): Model
    {
        $task = $this->taskRepository->findOrFail($taskId);

        // Prevent duplicate assignment
        if (! $task->assignees()->where('employees.id', $employeeId)->exists()) {
            $task->assignees()->attach($employeeId, [
                'assigned_by' => auth()->id(),
                'assigned_at' => now(),
            ]);

            $employee = Employee::findOrFail($employeeId);

            TaskActivityLog::create([
                'task_id' => $taskId,
                'user_id' => auth()->id(),
                'action' => 'assigned',
                'description' => "Assigned task to employee #{$employeeId}",
                'new_value' => (string) $employeeId,
            ]);

            event(new TaskAssigned($task, $employee));
        }

        return $task->load(['assignees', 'labels', 'column', 'creator']);
    }

    public function removeAssignee(int $taskId, int $employeeId): Model
    {
        $task = $this->taskRepository->findOrFail($taskId);
        $task->assignees()->detach($employeeId);

        TaskActivityLog::create([
            'task_id' => $taskId,
            'user_id' => auth()->id(),
            'action' => 'unassigned',
            'description' => "Removed assignee employee #{$employeeId}",
            'old_value' => (string) $employeeId,
        ]);

        return $task->load(['assignees', 'labels', 'column', 'creator']);
    }

    public function addComment(int $taskId, string $body): TaskComment
    {
        $this->taskRepository->findOrFail($taskId);

        $comment = TaskComment::create([
            'task_id' => $taskId,
            'user_id' => auth()->id(),
            'body' => $body,
        ]);

        TaskActivityLog::create([
            'task_id' => $taskId,
            'user_id' => auth()->id(),
            'action' => 'commented',
            'description' => 'Added a comment',
        ]);

        return $comment->load('user');
    }

    public function addAttachment(int $taskId, string $filePath, string $fileName, int $fileSize, string $mimeType): TaskAttachment
    {
        $this->taskRepository->findOrFail($taskId);

        $attachment = TaskAttachment::create([
            'task_id' => $taskId,
            'file_path' => $filePath,
            'file_name' => $fileName,
            'file_size' => $fileSize,
            'mime_type' => $mimeType,
            'uploaded_by' => auth()->id(),
        ]);

        TaskActivityLog::create([
            'task_id' => $taskId,
            'user_id' => auth()->id(),
            'action' => 'attachment_added',
            'description' => "Added attachment: {$fileName}",
        ]);

        return $attachment;
    }

    public function toggleChecklistItem(int $itemId): TaskChecklistItem
    {
        $item = TaskChecklistItem::findOrFail($itemId);

        $item->update([
            'is_completed' => ! $item->is_completed,
            'completed_by' => ! $item->is_completed ? auth()->id() : null,
            'completed_at' => ! $item->is_completed ? now() : null,
        ]);

        TaskActivityLog::create([
            'task_id' => $item->checklist->task_id,
            'user_id' => auth()->id(),
            'action' => $item->is_completed ? 'checklist_item_completed' : 'checklist_item_uncompleted',
            'description' => ($item->is_completed ? 'Completed' : 'Uncompleted') . " checklist item: {$item->title}",
        ]);

        return $item->fresh();
    }

    public function getMyTasks(int $userId): Collection
    {
        $employee = Employee::where('user_id', $userId)->first();

        if (! $employee) {
            return new Collection();
        }

        return $this->taskRepository->getByAssignee($employee->id);
    }

    public function paginateWithFilters(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        return $this->taskRepository->paginateWithFilters($filters, $perPage);
    }

    public function deleteTask(int $id): bool
    {
        return $this->taskRepository->delete($id);
    }
}

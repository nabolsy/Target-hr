<?php

namespace App\Listeners;

use App\Events\NotificationSent;
use App\Events\TaskAssigned;
use App\Models\NotificationLog;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Notify the assignee when a task is assigned to them.
 *
 * Source events:
 *   - Bulk assign via TaskService::createTask / updateTask (assignee_ids
 *     array → would need a wider event; today TaskAssigned only fires
 *     from assignTask single-target). The single-target path covers the
 *     user-visible flow ("Assign to {employee}" button).
 *
 * Skips when the assignee has no linked user account (the `user_id` on
 * the Employee row is NULL — happens for employees created without a
 * login, e.g. contractors). Notifications go to USERS, not employees.
 */
class SendTaskAssignedNotification
{
    public function handle(TaskAssigned $event): void
    {
        $task = $event->task->loadMissing(['board.department', 'column']);
        $employee = $event->employee;
        $userId = $employee->user_id;

        if (! $userId) {
            return;
        }

        $title = "You were assigned to a task";
        $boardName = $task->board?->name ?? 'a board';
        $columnName = $task->column?->name;
        $body = sprintf(
            '%s · %s%s',
            $task->title,
            $boardName,
            $columnName ? " · {$columnName}" : '',
        );

        $log = NotificationLog::create([
            'company_id' => $task->company_id,
            'user_id' => $userId,
            'type' => 'task',
            'title' => $title,
            'body' => $body,
            'data' => [
                'type' => 'task',
                'title' => $title,
                'message' => $body,
                'task_id' => $task->id,
                'task_title' => $task->title,
                'board_id' => $task->board_id,
                'board_name' => $boardName,
                'column_name' => $columnName,
                'department_name' => $task->board?->department?->name,
                'url' => $task->board_id ? "/boards/{$task->board_id}" : '/boards',
            ],
        ]);

        try {
            broadcast(new NotificationSent($log));
        } catch (Throwable $e) {
            Log::warning('TaskAssigned broadcast failed', [
                'user_id' => $userId,
                'task_id' => $task->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}

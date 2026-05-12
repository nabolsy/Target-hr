<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Board;
use App\Models\Employee;
use App\Models\Task;
use App\Models\TaskComment;
use App\Models\User;
use App\Services\Access\PermissionService;

/**
 * Task authorization via PermissionService.
 *
 * Read rules (via board.view): you can view a task if you can view its
 * board — OR if you're an explicit assignee on the task (even if you
 * can't otherwise see the board, e.g. a cross-department assignment).
 *
 * Write rules:
 *   - create   → task.create, scoped through the task's board
 *   - update   → task.update, scoped through the task's board,
 *                with a self-scope fallback for assignees updating
 *                their own tasks
 *   - delete   → task.update AND department-or-company scope (no self)
 *   - move     → task.move, scoped through the task's board,
 *                with a self-scope fallback for assignees moving their
 *                own tasks (drag-drop doesn't break for employees)
 *   - assign   → task.assign, scoped through the task's board
 *
 * The drag-drop UX is preserved because an Employee with task.update=self
 * AND task.move=self (implicitly allowed via the self fallback) can move
 * their own card between columns without hitting 403. Moving somebody
 * else's card still requires task.move at a scope that covers the board.
 */
class TaskPolicy
{
    public function __construct(private PermissionService $permissions)
    {
    }

    public function before(User $user, string $ability): ?bool
    {
        if ($user->role === UserRole::SuperAdmin) {
            return true;
        }

        return null;
    }

    public function viewAny(User $user): bool
    {
        return $this->permissions->can($user, 'board.view');
    }

    public function view(User $user, Task $task): bool
    {
        if ((int) $user->company_id !== (int) $task->company_id) {
            return false;
        }

        // Board-level visibility is the primary check. Any user who can
        // see the task's parent board can see the task.
        if ($this->canViewBoard($user, $task->board_id)) {
            return true;
        }

        // Fallback: explicit assignee always sees their own work.
        return $this->isAssignee($user, $task);
    }

    public function create(User $user): bool
    {
        return $this->permissions->can($user, 'task.create');
    }

    public function update(User $user, Task $task): bool
    {
        if ((int) $user->company_id !== (int) $task->company_id) {
            return false;
        }

        $scope = $this->permissions->getScope($user, 'task.update');

        // Self-scope: the user can only update their own assigned tasks.
        if ($scope === 'self') {
            return $this->isAssignee($user, $task);
        }

        if ($scope === 'company') {
            return true;
        }

        if ($scope === null) {
            // Final fallback: assignees can always update their own tasks
            // even if task.update isn't granted in their matrix row.
            return $this->isAssignee($user, $task);
        }

        // department scope: the task's board must be in the user's
        // visible set for task.update.
        return $this->boardInScope($user, $task->board_id, 'task.update');
    }

    public function delete(User $user, Task $task): bool
    {
        if ((int) $user->company_id !== (int) $task->company_id) {
            return false;
        }

        $scope = $this->permissions->getScope($user, 'task.update');

        // Deletion is strictly privileged — self is NOT enough.
        if ($scope === null || $scope === 'self') {
            return false;
        }

        if ($scope === 'company') {
            return true;
        }

        return $this->boardInScope($user, $task->board_id, 'task.update');
    }

    public function move(User $user, Task $task): bool
    {
        if ((int) $user->company_id !== (int) $task->company_id) {
            return false;
        }

        $scope = $this->permissions->getScope($user, 'task.move');

        // Assignees can always move their own tasks — this is what
        // keeps the drag-drop flow working for plain employees. They
        // don't need task.move in the matrix to drag a card between
        // columns on their own work.
        if ($this->isAssignee($user, $task)) {
            return true;
        }

        if ($scope === null || $scope === 'self') {
            return false;
        }

        if ($scope === 'company') {
            return true;
        }

        return $this->boardInScope($user, $task->board_id, 'task.move');
    }

    public function assign(User $user, Task $task): bool
    {
        if ((int) $user->company_id !== (int) $task->company_id) {
            return false;
        }

        $scope = $this->permissions->getScope($user, 'task.assign');
        if ($scope === null || $scope === 'self') {
            return false;
        }

        if ($scope === 'company') {
            return true;
        }

        return $this->boardInScope($user, $task->board_id, 'task.assign');
    }

    public function comment(User $user, Task $task): bool
    {
        if ((int) $user->company_id !== (int) $task->company_id) {
            return false;
        }

        // Company admins and anyone with board.manage can always
        // comment — they oversee boards even if they aren't on the
        // members pivot. Otherwise we require explicit board
        // membership so plain employees from other departments can't
        // comment on a board they can technically see.
        if ($user->role === UserRole::CompanyAdmin) {
            return true;
        }
        if ($this->permissions->can($user, 'board.manage')) {
            return true;
        }

        return $this->isBoardMember($user, $task->board_id);
    }

    public function deleteComment(User $user, Task $task, TaskComment $comment): bool
    {
        if ((int) $user->company_id !== (int) $task->company_id) {
            return false;
        }

        // Authors can always delete their own comments. Board admins
        // (anyone with task.update at department-or-company scope on
        // this board) may moderate.
        if ((int) $comment->user_id === (int) $user->id) {
            return true;
        }

        $scope = $this->permissions->getScope($user, 'task.update');
        if ($scope === null || $scope === 'self') {
            return false;
        }
        if ($scope === 'company') {
            return true;
        }

        return $this->boardInScope($user, $task->board_id, 'task.update');
    }

    protected function isBoardMember(User $user, ?int $boardId): bool
    {
        if (! $boardId) {
            return false;
        }
        $board = Board::find($boardId);
        if (! $board) {
            return false;
        }
        if ((int) $user->company_id !== (int) $board->company_id) {
            return false;
        }

        $employeeId = $this->permissions->employeeIdForSelf($user);
        if (! $employeeId) {
            return false;
        }

        return $board->members()->where('employees.id', $employeeId)->exists();
    }

    // ── Helpers ──────────────────────────────────────────────────────────

    protected function canViewBoard(User $user, ?int $boardId): bool
    {
        if (! $boardId) {
            return false;
        }
        $board = Board::find($boardId);
        if (! $board) {
            return false;
        }

        if ((int) $user->company_id !== (int) $board->company_id) {
            return false;
        }

        $scope = $this->permissions->getScope($user, 'board.view');
        if ($scope === null) {
            return false;
        }
        if ($scope === 'company') {
            return true;
        }

        // Company-wide boards visible to everyone with any board.view.
        if ($board->department_id === null) {
            return true;
        }

        $visible = $this->permissions->visibleDepartmentIds($user, 'board.view');
        if ($visible === null) {
            return true;
        }
        if (in_array((int) $board->department_id, $visible, true)) {
            return true;
        }

        // Final: board_members pivot membership.
        $employeeId = $this->permissions->employeeIdForSelf($user);
        if ($employeeId && $board->members()->where('employees.id', $employeeId)->exists()) {
            return true;
        }

        return false;
    }

    protected function boardInScope(User $user, ?int $boardId, string $permission): bool
    {
        if (! $boardId) {
            return false;
        }
        $board = Board::find($boardId);
        if (! $board) {
            return false;
        }

        if ($board->department_id === null) {
            // Company-wide board: only company-scope permission can act.
            return $this->permissions->getScope($user, $permission) === 'company';
        }

        $visible = $this->permissions->visibleDepartmentIds($user, $permission);
        if ($visible === null) {
            return true;
        }

        return in_array((int) $board->department_id, $visible, true);
    }

    protected function isAssignee(User $user, Task $task): bool
    {
        $employeeId = $this->permissions->employeeIdForSelf($user);
        if (! $employeeId) {
            return false;
        }

        return $task->assignees()->where('employees.id', $employeeId)->exists();
    }
}

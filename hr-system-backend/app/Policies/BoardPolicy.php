<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Board;
use App\Models\Employee;
use App\Models\User;
use App\Services\Access\PermissionService;

/**
 * Board authorization via PermissionService.
 *
 * Read rules (via board.view):
 *   - company scope  → all boards in the company
 *   - managed_department / own_department → boards whose department_id
 *     is in the user's visible set, PLUS boards with NULL department_id
 *     (company-wide boards visible to everyone), PLUS boards where the
 *     user's employee record is an explicit board_members pivot row.
 *   - no permission → 0 boards.
 *
 * Write rules (via board.manage):
 *   - company scope → can create/update/delete/archive any company board
 *   - managed_department / own_department → only within the user's
 *     visible department set. Editing a company-wide (NULL department)
 *     board requires company scope.
 *
 * Kept backwards compatible: the SuperAdmin before() shortcut stays, and
 * the legacy UserRole enum column is still the canonical role source for
 * RoleMiddleware and other callers.
 */
class BoardPolicy
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

    public function view(User $user, Board $board): bool
    {
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

        // Company-wide boards (NULL department_id) are visible to anyone
        // with any level of board.view permission.
        if ($board->department_id === null) {
            return true;
        }

        // Department-scoped board: the department must be in the user's
        // visible set.
        $visible = $this->permissions->visibleDepartmentIds($user, 'board.view');
        if ($visible === null) {
            return true;
        }
        if (in_array((int) $board->department_id, $visible, true)) {
            return true;
        }

        // Fallback: the user is an explicit member of the board via the
        // board_members pivot (added in an earlier session).
        $employeeId = $this->permissions->employeeIdForSelf($user);
        if ($employeeId && $board->members()->where('employees.id', $employeeId)->exists()) {
            return true;
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $this->permissions->can($user, 'board.manage');
    }

    public function update(User $user, Board $board): bool
    {
        if ((int) $user->company_id !== (int) $board->company_id) {
            return false;
        }

        $scope = $this->permissions->getScope($user, 'board.manage');
        if ($scope === null) {
            return false;
        }
        if ($scope === 'company') {
            return true;
        }

        // Updating a company-wide board requires company scope — a
        // department manager cannot edit a board that belongs to no
        // specific department.
        if ($board->department_id === null) {
            return false;
        }

        $visible = $this->permissions->visibleDepartmentIds($user, 'board.manage');
        if ($visible === null) {
            return true;
        }

        return in_array((int) $board->department_id, $visible, true);
    }

    public function delete(User $user, Board $board): bool
    {
        return $this->update($user, $board);
    }

    public function archive(User $user, Board $board): bool
    {
        return $this->update($user, $board);
    }

    /**
     * Managing board members (add/remove employees from the board_members
     * pivot) is a manage-level write.
     */
    public function manageMembers(User $user, Board $board): bool
    {
        return $this->update($user, $board);
    }

    /**
     * Managing columns (create/rename/archive/restore/delete) is a
     * manage-level write on the parent board.
     */
    public function manageColumns(User $user, Board $board): bool
    {
        return $this->update($user, $board);
    }
}

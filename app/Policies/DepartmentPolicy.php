<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Department;
use App\Models\User;
use App\Services\Access\PermissionService;

/**
 * Department authorization via PermissionService.
 *
 * - viewAny gated by `department.view`
 * - view gated per-record by the user's visible department IDs
 * - create / update / delete gated by `department.manage` (company scope)
 *
 * Kept backwards compatible:
 *   - SuperAdmin shortcut in before()
 *   - Tenant boundary check on every per-record method
 */
class DepartmentPolicy
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
        return $this->permissions->can($user, 'department.view');
    }

    public function view(User $user, Department $department): bool
    {
        if ((int) $user->company_id !== (int) $department->company_id) {
            return false;
        }

        $scope = $this->permissions->getScope($user, 'department.view');

        if ($scope === null) {
            return false;
        }

        if ($scope === 'company') {
            return true;
        }

        $visible = $this->permissions->visibleDepartmentIds($user, 'department.view');

        if ($visible === null) {
            return true;
        }

        return in_array((int) $department->id, $visible, true);
    }

    public function create(User $user): bool
    {
        return $this->permissions->can($user, 'department.manage');
    }

    public function update(User $user, Department $department): bool
    {
        if ((int) $user->company_id !== (int) $department->company_id) {
            return false;
        }

        $scope = $this->permissions->getScope($user, 'department.manage');

        if ($scope === null) {
            return false;
        }

        if ($scope === 'company') {
            return true;
        }

        $visible = $this->permissions->visibleDepartmentIds($user, 'department.manage');

        return $visible !== null
            && in_array((int) $department->id, $visible, true);
    }

    public function delete(User $user, Department $department): bool
    {
        return $this->update($user, $department);
    }

    /**
     * Manager assignment / removal — treated as a manage-level write.
     */
    public function assignManager(User $user, Department $department): bool
    {
        return $this->update($user, $department);
    }
}

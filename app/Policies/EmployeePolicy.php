<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Employee;
use App\Models\User;
use App\Services\Access\PermissionService;

/**
 * Employee authorization, refactored to delegate to PermissionService for
 * scope resolution. Kept backwards compatible with the legacy `UserRole`
 * enum via:
 *   - The `before()` super-admin shortcut (unchanged).
 *   - PermissionService internally checking the legacy enum for
 *     SuperAdmin and falling back to Spatie role+permission grants for
 *     everyone else.
 *
 * The legacy hardcoded enum branches in this file have been removed —
 * scope is now declared in config/role_access.php and resolved at runtime,
 * so adding a new role is a config edit, not a policy edit.
 */
class EmployeePolicy
{
    public function __construct(private PermissionService $permissions)
    {
    }

    /**
     * SuperAdmin can do everything. This mirrors the policy convention
     * already used across the other 18 policies in app/Policies/*.
     */
    public function before(User $user, string $ability): ?bool
    {
        if ($user->role === UserRole::SuperAdmin) {
            return true;
        }

        return null;
    }

    public function viewAny(User $user): bool
    {
        return $this->permissions->can($user, 'employee.view');
    }

    public function view(User $user, Employee $employee): bool
    {
        // Tenant boundary — never look across companies.
        if ((int) $user->company_id !== (int) $employee->company_id) {
            return false;
        }

        $scope = $this->permissions->getScope($user, 'employee.view');

        if ($scope === null) {
            return false;
        }

        if ($scope === 'company') {
            return true;
        }

        // For self scope, the employee row must be the user's own.
        if ($scope === 'self') {
            return (int) $user->id === (int) $employee->user_id;
        }

        // own_department / managed_department: row's department must be in
        // the visible set returned by the service.
        $visible = $this->permissions->visibleDepartmentIds($user, 'employee.view');

        if ($visible === null) {
            return true; // company shortcut, defensive
        }

        return $employee->department_id !== null
            && in_array((int) $employee->department_id, $visible, true);
    }

    public function create(User $user): bool
    {
        return $this->permissions->can($user, 'employee.create');
    }

    public function update(User $user, Employee $employee): bool
    {
        if ((int) $user->company_id !== (int) $employee->company_id) {
            return false;
        }

        $scope = $this->permissions->getScope($user, 'employee.update');

        if ($scope === null) {
            // Allow self-edit even if employee.update is not granted: an
            // Employee role can still update their own profile (self scope)
            // via employee.view's self semantics. Falls back to view check.
            if ($this->permissions->isSelfScope($user, 'employee.view')) {
                return (int) $user->id === (int) $employee->user_id;
            }

            return false;
        }

        if ($scope === 'company') {
            return true;
        }

        if ($scope === 'self') {
            return (int) $user->id === (int) $employee->user_id;
        }

        $visible = $this->permissions->visibleDepartmentIds($user, 'employee.update');

        if ($visible === null) {
            return true;
        }

        return $employee->department_id !== null
            && in_array((int) $employee->department_id, $visible, true);
    }

    public function delete(User $user, Employee $employee): bool
    {
        if ((int) $user->company_id !== (int) $employee->company_id) {
            return false;
        }

        // Self cannot delete self — destructive op requires company scope.
        $scope = $this->permissions->getScope($user, 'employee.delete');

        if ($scope === null) {
            return false;
        }

        if ($scope === 'company') {
            return true;
        }

        if ($scope === 'self') {
            return false; // explicit guardrail
        }

        $visible = $this->permissions->visibleDepartmentIds($user, 'employee.delete');

        return $visible !== null
            && $employee->department_id !== null
            && in_array((int) $employee->department_id, $visible, true);
    }

    /**
     * Status changes (active / probation / on_leave / terminated) are
     * treated as a write — same scope as update.
     */
    public function updateStatus(User $user, Employee $employee): bool
    {
        return $this->update($user, $employee);
    }

    /**
     * Department transfer is a separate, more privileged operation —
     * tied to the explicit employee.transfer permission.
     */
    public function transfer(User $user, Employee $employee): bool
    {
        if ((int) $user->company_id !== (int) $employee->company_id) {
            return false;
        }

        $scope = $this->permissions->getScope($user, 'employee.transfer');

        if ($scope === null || $scope === 'self') {
            return false;
        }

        if ($scope === 'company') {
            return true;
        }

        $visible = $this->permissions->visibleDepartmentIds($user, 'employee.transfer');

        return $visible !== null
            && $employee->department_id !== null
            && in_array((int) $employee->department_id, $visible, true);
    }
}

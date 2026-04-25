<?php

namespace App\Services\Access;

use App\Enums\UserRole;
use App\Models\Department;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Resolves a user's effective access scope for a given permission key by
 * walking their assigned Spatie roles and consulting config/role_access.php.
 *
 * The "broadest scope wins" rule is applied across roles, so a user with
 * both an "Employee" role (own_department) and a "Recruiter" role (company)
 * gets `company` for any permission both roles cover.
 *
 * Super Admin is short-circuited to `company` for everything — this mirrors
 * the existing policy `before()` shortcut and keeps platform admins
 * omnipotent without any matrix entries.
 *
 * Usage from a policy / controller / service:
 *
 *   if (! $perms->can($user, 'employee.view')) abort(403);
 *
 *   $departmentIds = $perms->visibleDepartmentIds($user, 'employee.view');
 *   // returns null  → no restriction (company scope)
 *   // returns []    → no access (denied — caller should already have aborted)
 *   // returns [3,5] → caller must filter by department_id IN (3,5)
 */
class PermissionService
{
    private array $matrix;

    private array $scopeOrder;

    public function __construct()
    {
        $this->matrix = config('role_access.roles', []);
        $this->scopeOrder = config('role_access.scope_order', [
            'self', 'own_department', 'managed_department', 'company',
        ]);
    }

    /**
     * The broadest scope this user has for the given permission across all
     * their assigned roles, or null if the permission is not granted at all.
     */
    public function getScope(User $user, string $permission): ?string
    {
        // Full-access short-circuit: Super Admin and Company Admin
        // ALWAYS get company scope for ANY permission, regardless of
        // what config/role_access.php says. This makes admin access
        // future-proof — new permissions added later don't need a
        // matrix update for these two roles to keep working.
        if ($this->hasFullCompanyAccess($user)) {
            return 'company';
        }

        $broadest = null;
        $broadestRank = -1;

        // Walk Spatie roles by name and look them up in the matrix.
        // Users may have additional Spatie permissions assigned directly
        // (outside the matrix); for those we fall back to `company` scope
        // since direct grants imply explicit unrestricted access.
        foreach ($user->getRoleNames() as $roleName) {
            $roleMatrix = $this->matrix[$roleName] ?? null;
            if (! $roleMatrix) {
                continue;
            }
            $scope = $roleMatrix[$permission] ?? null;
            if (! $scope) {
                continue;
            }
            $rank = array_search($scope, $this->scopeOrder, true);
            if ($rank === false) {
                continue;
            }
            if ($rank > $broadestRank) {
                $broadestRank = $rank;
                $broadest = $scope;
            }
        }

        // Direct permission grants (not via matrix role) imply company scope.
        if ($broadest === null && $user->hasPermissionTo($permission)) {
            return 'company';
        }

        return $broadest;
    }

    /**
     * Binary "does this user have this permission at any scope?".
     * Cheaper than getScope() when scope doesn't matter (e.g. a button gate).
     */
    public function can(User $user, string $permission): bool
    {
        // Explicit short-circuit so callers that invoke can() directly
        // don't pay the matrix-walk cost for admins.
        if ($this->hasFullCompanyAccess($user)) {
            return true;
        }

        return $this->getScope($user, $permission) !== null;
    }

    /**
     * Resolve the set of department IDs the user is allowed to see for the
     * given permission. Returns:
     *
     *   null  → no restriction (company scope or super admin)
     *   []    → no access at all (caller should have already gated)
     *   [...] → explicit list of department IDs to filter by
     *
     * Notes on the scopes:
     *   - self: returns the user's own department only (so colleagues are
     *     visible at the column level even though `self` semantically means
     *     "own row" — most queries that need true row-level isolation will
     *     also call employeeIdForSelf() below).
     *   - own_department: returns the user's primary department + descendants.
     *   - managed_department: returns the departments the user is the
     *     manager_id on + descendants.
     *   - company: returns null (no filter).
     */
    public function visibleDepartmentIds(User $user, string $permission): ?array
    {
        // Full-access short-circuit. `null` = no filter, i.e. every
        // department in the user's company is visible.
        if ($this->hasFullCompanyAccess($user)) {
            return null;
        }

        $scope = $this->getScope($user, $permission);

        if ($scope === null) {
            return [];
        }

        if ($scope === 'company') {
            return null;
        }

        $employee = $this->employeeFor($user);

        if ($scope === 'self' || $scope === 'own_department') {
            if (! $employee || ! $employee->department_id) {
                return [];
            }

            return $this->departmentSubtreeIds([$employee->department_id]);
        }

        if ($scope === 'managed_department') {
            $managedRoots = Department::where('company_id', $user->company_id)
                ->where('manager_id', $user->id)
                ->pluck('id')
                ->all();

            if (empty($managedRoots)) {
                return [];
            }

            return $this->departmentSubtreeIds($managedRoots);
        }

        return [];
    }

    /**
     * For row-level "own record" enforcement (e.g. an Employee user can
     * only see/edit their own employee row at scope=self).
     *
     * Returns the employee_id linked to this user's account, or null if
     * the user has no employee record yet.
     */
    public function employeeIdForSelf(User $user): ?int
    {
        return $this->employeeFor($user)?->id;
    }

    /**
     * Resolve the set of employee IDs the user is allowed to see for the
     * given permission. Mirrors visibleDepartmentIds() but for any model
     * scoped via employee_id (LeaveRequest, AttendanceRecord, Document, …).
     *
     * Returns:
     *   null  → no restriction (company scope)
     *   []    → no access at all
     *   [...] → explicit list of employee IDs to filter by
     *
     * Caller can then do: $query->whereIn('employee_id', $ids)
     *
     * For scope=self this returns just the user's own employee row;
     * for own_department / managed_department it pre-resolves the
     * department subtree and returns every employee in those departments.
     */
    public function visibleEmployeeIds(User $user, string $permission): ?array
    {
        // Full-access short-circuit. `null` = no filter, i.e. every
        // employee in the user's company is visible.
        if ($this->hasFullCompanyAccess($user)) {
            return null;
        }

        $scope = $this->getScope($user, $permission);

        if ($scope === null) {
            return [];
        }

        if ($scope === 'company') {
            return null;
        }

        if ($scope === 'self') {
            $self = $this->employeeIdForSelf($user);
            return $self ? [$self] : [];
        }

        $departmentIds = $this->visibleDepartmentIds($user, $permission);

        if ($departmentIds === null) {
            return null;
        }

        if (empty($departmentIds)) {
            return [];
        }

        return Employee::whereIn('department_id', $departmentIds)
            ->where('company_id', $user->company_id)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    /**
     * Convenience: is this user "self-only" for the given permission?
     */
    public function isSelfScope(User $user, string $permission): bool
    {
        return $this->getScope($user, $permission) === 'self';
    }

    /**
     * Walk the department hierarchy from a set of root IDs and return every
     * descendant. Performed iteratively to avoid recursion depth issues.
     * Returns the roots plus all descendants as a unique int array.
     */
    private function departmentSubtreeIds(array $rootIds): array
    {
        $all = collect($rootIds)->map(fn ($id) => (int) $id)->all();
        $frontier = $all;

        while (! empty($frontier)) {
            $children = Department::whereIn('parent_id', $frontier)
                ->pluck('id')
                ->all();

            $next = array_values(array_diff($children, $all));
            if (empty($next)) {
                break;
            }
            $all = array_merge($all, $next);
            $frontier = $next;
        }

        return array_values(array_unique(array_map('intval', $all)));
    }

    private function employeeFor(User $user): ?Employee
    {
        // The Employee record is keyed by user_id. Use a one-time per-request
        // cache on the user object to avoid redundant queries.
        if (! property_exists($user, 'cachedEmployeeForAccess')) {
            $user->cachedEmployeeForAccess = Employee::where('user_id', $user->id)->first();
        }

        return $user->cachedEmployeeForAccess;
    }

    private function isSuperAdmin(User $user): bool
    {
        return $user->role === UserRole::SuperAdmin;
    }

    /**
     * Company Admin detection. Checks BOTH the legacy UserRole enum
     * column and the Spatie role name so the bypass stays consistent
     * even if the two drift (e.g. a user whose Spatie role was synced
     * but whose legacy column wasn't, or vice versa).
     */
    private function isCompanyAdmin(User $user): bool
    {
        if ($user->role === UserRole::CompanyAdmin) {
            return true;
        }

        // 'Company Admin' is the Spatie role name (no enum value match),
        // so User::hasRole() falls through to the Spatie trait for it.
        return method_exists($user, 'hasRole') && $user->hasRole('Company Admin');
    }

    /**
     * True when the user should bypass all scope checks and see every
     * record in their company. Super Admin (platform owner) and
     * Company Admin (tenant owner) both qualify.
     */
    private function hasFullCompanyAccess(User $user): bool
    {
        return $this->isSuperAdmin($user) || $this->isCompanyAdmin($user);
    }
}

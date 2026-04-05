<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Asset;
use App\Models\Employee;
use App\Models\User;

class AssetPolicy
{
    /**
     * SuperAdmin can do everything.
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
        return in_array($user->role, [
            UserRole::CompanyAdmin,
            UserRole::HrManager,
            UserRole::DepartmentManager,
            UserRole::Employee,
        ]);
    }

    public function view(User $user, Asset $asset): bool
    {
        // CompanyAdmin and HrManager can view assets in their company
        if (
            in_array($user->role, [UserRole::CompanyAdmin, UserRole::HrManager])
            && (int) $user->company_id === (int) $asset->company_id
        ) {
            return true;
        }

        // Employee can view their own assigned assets
        if (in_array($user->role, [UserRole::Employee, UserRole::DepartmentManager])) {
            $employee = Employee::where('user_id', $user->id)->first();
            if ($employee) {
                return $asset->currentAssignment()
                    ->where('employee_id', $employee->id)
                    ->exists();
            }
        }

        return false;
    }

    public function create(User $user): bool
    {
        return in_array($user->role, [
            UserRole::CompanyAdmin,
            UserRole::HrManager,
        ]);
    }

    public function update(User $user, Asset $asset): bool
    {
        return in_array($user->role, [UserRole::CompanyAdmin, UserRole::HrManager])
            && (int) $user->company_id === (int) $asset->company_id;
    }

    public function delete(User $user, Asset $asset): bool
    {
        return in_array($user->role, [UserRole::CompanyAdmin, UserRole::HrManager])
            && (int) $user->company_id === (int) $asset->company_id;
    }

    public function assign(User $user, Asset $asset): bool
    {
        return in_array($user->role, [UserRole::CompanyAdmin, UserRole::HrManager])
            && (int) $user->company_id === (int) $asset->company_id;
    }

    public function return(User $user, Asset $asset): bool
    {
        return in_array($user->role, [UserRole::CompanyAdmin, UserRole::HrManager])
            && (int) $user->company_id === (int) $asset->company_id;
    }
}

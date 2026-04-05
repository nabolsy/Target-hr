<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Department;
use App\Models\User;

class DepartmentPolicy
{
    public function viewAny(User $user): bool
    {
        return in_array($user->role, [
            UserRole::SuperAdmin,
            UserRole::CompanyAdmin,
            UserRole::HrManager,
        ]);
    }

    public function view(User $user, Department $department): bool
    {
        if ($user->role === UserRole::SuperAdmin) {
            return true;
        }

        return (int) $user->company_id === (int) $department->company_id;
    }

    public function create(User $user): bool
    {
        return in_array($user->role, [
            UserRole::SuperAdmin,
            UserRole::CompanyAdmin,
            UserRole::HrManager,
        ]);
    }

    public function update(User $user, Department $department): bool
    {
        if ($user->role === UserRole::SuperAdmin) {
            return true;
        }

        return in_array($user->role, [UserRole::CompanyAdmin, UserRole::HrManager])
            && (int) $user->company_id === (int) $department->company_id;
    }

    public function delete(User $user, Department $department): bool
    {
        if ($user->role === UserRole::SuperAdmin) {
            return true;
        }

        return in_array($user->role, [UserRole::CompanyAdmin, UserRole::HrManager])
            && (int) $user->company_id === (int) $department->company_id;
    }
}

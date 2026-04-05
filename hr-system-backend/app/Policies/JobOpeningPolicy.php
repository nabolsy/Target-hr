<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\JobOpening;
use App\Models\User;

class JobOpeningPolicy
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
        ]);
    }

    public function view(User $user, JobOpening $jobOpening): bool
    {
        return in_array($user->role, [
            UserRole::CompanyAdmin,
            UserRole::HrManager,
            UserRole::DepartmentManager,
        ]) && (int) $user->company_id === (int) $jobOpening->company_id;
    }

    public function create(User $user): bool
    {
        return in_array($user->role, [
            UserRole::CompanyAdmin,
            UserRole::HrManager,
        ]);
    }

    public function update(User $user, JobOpening $jobOpening): bool
    {
        return in_array($user->role, [UserRole::CompanyAdmin, UserRole::HrManager])
            && (int) $user->company_id === (int) $jobOpening->company_id;
    }

    public function delete(User $user, JobOpening $jobOpening): bool
    {
        return in_array($user->role, [UserRole::CompanyAdmin, UserRole::HrManager])
            && (int) $user->company_id === (int) $jobOpening->company_id;
    }
}

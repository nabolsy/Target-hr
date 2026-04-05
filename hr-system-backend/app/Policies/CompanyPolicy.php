<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Company;
use App\Models\User;

class CompanyPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->role === UserRole::SuperAdmin;
    }

    public function view(User $user, Company $company): bool
    {
        if ($user->role === UserRole::SuperAdmin) {
            return true;
        }

        return (int) $user->company_id === (int) $company->id;
    }

    public function create(User $user): bool
    {
        return $user->role === UserRole::SuperAdmin;
    }

    public function update(User $user, Company $company): bool
    {
        if ($user->role === UserRole::SuperAdmin) {
            return true;
        }

        return $user->role === UserRole::CompanyAdmin
            && (int) $user->company_id === (int) $company->id;
    }

    public function delete(User $user, Company $company): bool
    {
        return $user->role === UserRole::SuperAdmin;
    }

    public function updateStatus(User $user, Company $company): bool
    {
        return $user->role === UserRole::SuperAdmin;
    }
}

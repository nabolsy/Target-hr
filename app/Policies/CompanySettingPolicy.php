<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\CompanySetting;
use App\Models\User;

class CompanySettingPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        if ($user->role === UserRole::SuperAdmin) {
            return true;
        }

        return null;
    }

    public function view(User $user): bool
    {
        return in_array($user->role, [
            UserRole::CompanyAdmin,
            UserRole::HrManager,
        ]);
    }

    public function update(User $user): bool
    {
        return $user->role === UserRole::CompanyAdmin;
    }
}

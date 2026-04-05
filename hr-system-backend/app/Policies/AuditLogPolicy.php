<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\AuditLog;
use App\Models\User;

class AuditLogPolicy
{
    public function viewAny(User $user): bool
    {
        return in_array($user->role, [UserRole::SuperAdmin, UserRole::CompanyAdmin]);
    }

    public function view(User $user, AuditLog $auditLog): bool
    {
        if ($user->role === UserRole::SuperAdmin) {
            return true;
        }

        return $user->role === UserRole::CompanyAdmin
            && (int) $user->company_id === (int) $auditLog->company_id;
    }
}

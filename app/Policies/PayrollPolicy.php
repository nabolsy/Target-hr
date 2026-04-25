<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\PayrollPeriod;
use App\Models\User;

class PayrollPolicy
{
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
        ]) || $user->can('view_salary');
    }

    public function view(User $user, PayrollPeriod $payrollPeriod): bool
    {
        if ((int) $user->company_id !== (int) $payrollPeriod->company_id) {
            return false;
        }

        return in_array($user->role, [
            UserRole::CompanyAdmin,
            UserRole::HrManager,
        ]) || $user->can('view_salary');
    }

    public function generate(User $user): bool
    {
        return in_array($user->role, [
            UserRole::CompanyAdmin,
            UserRole::HrManager,
        ]);
    }

    public function lock(User $user, PayrollPeriod $payrollPeriod): bool
    {
        if ((int) $user->company_id !== (int) $payrollPeriod->company_id) {
            return false;
        }

        return in_array($user->role, [
            UserRole::CompanyAdmin,
            UserRole::HrManager,
        ]);
    }

    public function export(User $user, PayrollPeriod $payrollPeriod): bool
    {
        if ((int) $user->company_id !== (int) $payrollPeriod->company_id) {
            return false;
        }

        return in_array($user->role, [
            UserRole::CompanyAdmin,
            UserRole::HrManager,
        ]) || $user->can('view_salary');
    }
}

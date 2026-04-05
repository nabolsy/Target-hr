<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\SalaryStructure;
use App\Models\User;

class SalaryStructurePolicy
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

    public function view(User $user, SalaryStructure $salaryStructure): bool
    {
        if ((int) $user->company_id !== (int) $salaryStructure->company_id) {
            return false;
        }

        return in_array($user->role, [
            UserRole::CompanyAdmin,
            UserRole::HrManager,
        ]) || $user->can('view_salary');
    }

    public function create(User $user): bool
    {
        return in_array($user->role, [
            UserRole::CompanyAdmin,
            UserRole::HrManager,
        ]);
    }

    public function update(User $user, SalaryStructure $salaryStructure): bool
    {
        if ((int) $user->company_id !== (int) $salaryStructure->company_id) {
            return false;
        }

        return in_array($user->role, [
            UserRole::CompanyAdmin,
            UserRole::HrManager,
        ]);
    }

    public function delete(User $user, SalaryStructure $salaryStructure): bool
    {
        if ((int) $user->company_id !== (int) $salaryStructure->company_id) {
            return false;
        }

        return in_array($user->role, [
            UserRole::CompanyAdmin,
            UserRole::HrManager,
        ]);
    }
}

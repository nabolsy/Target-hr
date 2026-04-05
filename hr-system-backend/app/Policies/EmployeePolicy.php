<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Employee;
use App\Models\User;

class EmployeePolicy
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

    public function view(User $user, Employee $employee): bool
    {
        // CompanyAdmin and HrManager can view employees in their company
        if (
            in_array($user->role, [UserRole::CompanyAdmin, UserRole::HrManager])
            && (int) $user->company_id === (int) $employee->company_id
        ) {
            return true;
        }

        // DepartmentManager can view employees in their department
        if ($user->role === UserRole::DepartmentManager) {
            $managerEmployee = Employee::where('user_id', $user->id)->first();
            if ($managerEmployee && (int) $managerEmployee->department_id === (int) $employee->department_id) {
                return true;
            }
        }

        // Employee can view own profile
        if ($user->role === UserRole::Employee) {
            return (int) $user->id === (int) $employee->user_id;
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

    public function update(User $user, Employee $employee): bool
    {
        // CompanyAdmin and HrManager can update employees in their company
        if (
            in_array($user->role, [UserRole::CompanyAdmin, UserRole::HrManager])
            && (int) $user->company_id === (int) $employee->company_id
        ) {
            return true;
        }

        // DepartmentManager can update employees in their department
        if ($user->role === UserRole::DepartmentManager) {
            $managerEmployee = Employee::where('user_id', $user->id)->first();
            if ($managerEmployee && (int) $managerEmployee->department_id === (int) $employee->department_id) {
                return true;
            }
        }

        // Employee can update own profile
        if ($user->role === UserRole::Employee) {
            return (int) $user->id === (int) $employee->user_id;
        }

        return false;
    }

    public function delete(User $user, Employee $employee): bool
    {
        return in_array($user->role, [UserRole::CompanyAdmin, UserRole::HrManager])
            && (int) $user->company_id === (int) $employee->company_id;
    }

    public function updateStatus(User $user, Employee $employee): bool
    {
        return in_array($user->role, [UserRole::CompanyAdmin, UserRole::HrManager])
            && (int) $user->company_id === (int) $employee->company_id;
    }
}

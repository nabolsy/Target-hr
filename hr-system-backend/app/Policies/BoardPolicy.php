<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Board;
use App\Models\Employee;
use App\Models\User;

class BoardPolicy
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

    public function view(User $user, Board $board): bool
    {
        // CompanyAdmin and HrManager can view boards in their company
        if (
            in_array($user->role, [UserRole::CompanyAdmin, UserRole::HrManager])
            && (int) $user->company_id === (int) $board->company_id
        ) {
            return true;
        }

        // DepartmentManager can view boards for their department or company-wide boards
        if ($user->role === UserRole::DepartmentManager) {
            $managerEmployee = Employee::where('user_id', $user->id)->first();
            if ($managerEmployee) {
                return (int) $user->company_id === (int) $board->company_id
                    && ($board->department_id === null || (int) $managerEmployee->department_id === (int) $board->department_id);
            }
        }

        // Employee can view boards in their company (company-wide or their department)
        if ($user->role === UserRole::Employee) {
            $employee = Employee::where('user_id', $user->id)->first();
            if ($employee) {
                return (int) $user->company_id === (int) $board->company_id
                    && ($board->department_id === null || (int) $employee->department_id === (int) $board->department_id);
            }
        }

        return false;
    }

    public function create(User $user): bool
    {
        return in_array($user->role, [
            UserRole::CompanyAdmin,
            UserRole::HrManager,
            UserRole::DepartmentManager,
        ]);
    }

    public function update(User $user, Board $board): bool
    {
        if (
            in_array($user->role, [UserRole::CompanyAdmin, UserRole::HrManager])
            && (int) $user->company_id === (int) $board->company_id
        ) {
            return true;
        }

        // DepartmentManager can update own department boards
        if ($user->role === UserRole::DepartmentManager) {
            $managerEmployee = Employee::where('user_id', $user->id)->first();
            if ($managerEmployee && $board->department_id
                && (int) $managerEmployee->department_id === (int) $board->department_id
            ) {
                return true;
            }
        }

        return false;
    }

    public function delete(User $user, Board $board): bool
    {
        return $this->update($user, $board);
    }

    public function archive(User $user, Board $board): bool
    {
        return $this->update($user, $board);
    }
}

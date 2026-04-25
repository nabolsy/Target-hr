<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Employee;
use App\Models\OnboardingChecklist;
use App\Models\OnboardingChecklistItem;
use App\Models\User;

class OnboardingChecklistPolicy
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

    public function view(User $user, OnboardingChecklist $checklist): bool
    {
        // CompanyAdmin and HrManager can view checklists in their company
        if (
            in_array($user->role, [UserRole::CompanyAdmin, UserRole::HrManager])
            && (int) $user->company_id === (int) $checklist->company_id
        ) {
            return true;
        }

        // Employee can view their own checklist
        if ($user->role === UserRole::Employee || $user->role === UserRole::DepartmentManager) {
            $employee = Employee::where('user_id', $user->id)->first();
            if ($employee && (int) $employee->id === (int) $checklist->employee_id) {
                return true;
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

    public function update(User $user, OnboardingChecklist $checklist): bool
    {
        return in_array($user->role, [UserRole::CompanyAdmin, UserRole::HrManager])
            && (int) $user->company_id === (int) $checklist->company_id;
    }

    public function delete(User $user, OnboardingChecklist $checklist): bool
    {
        return in_array($user->role, [UserRole::CompanyAdmin, UserRole::HrManager])
            && (int) $user->company_id === (int) $checklist->company_id;
    }

    public function completeItem(User $user, OnboardingChecklistItem $item): bool
    {
        $checklist = $item->checklist;

        // CompanyAdmin and HrManager can complete items in their company
        if (
            in_array($user->role, [UserRole::CompanyAdmin, UserRole::HrManager])
            && (int) $user->company_id === (int) $checklist->company_id
        ) {
            return true;
        }

        // Employee can complete items assigned to them or on their own checklist
        $employee = Employee::where('user_id', $user->id)->first();
        if ($employee) {
            // Can complete items on their own checklist
            if ((int) $employee->id === (int) $checklist->employee_id) {
                return true;
            }

            // Can complete items assigned to them
            if ($item->assigned_to && (int) $item->assigned_to === (int) $user->id) {
                return true;
            }
        }

        return false;
    }
}

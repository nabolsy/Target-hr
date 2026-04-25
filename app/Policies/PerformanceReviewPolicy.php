<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Employee;
use App\Models\PerformanceReview;
use App\Models\User;

class PerformanceReviewPolicy
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

    public function view(User $user, PerformanceReview $review): bool
    {
        // CompanyAdmin can view all reviews in their company
        if (
            $user->role === UserRole::CompanyAdmin
            && (int) $user->company_id === (int) $review->company_id
        ) {
            return true;
        }

        // HrManager can view all reviews in their company
        if (
            $user->role === UserRole::HrManager
            && (int) $user->company_id === (int) $review->company_id
        ) {
            return true;
        }

        // DepartmentManager can view reviews of their direct reports
        if ($user->role === UserRole::DepartmentManager) {
            return $this->isDirectReport($user, $review->employee_id)
                || (int) $user->id === (int) $review->reviewer_id;
        }

        // Employee can view their own reviews
        if ($user->role === UserRole::Employee) {
            $employee = Employee::where('user_id', $user->id)->first();
            return $employee && (int) $employee->id === (int) $review->employee_id;
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

    public function submit(User $user, PerformanceReview $review): bool
    {
        // CompanyAdmin and HrManager can submit any review in their company
        if (
            in_array($user->role, [UserRole::CompanyAdmin, UserRole::HrManager])
            && (int) $user->company_id === (int) $review->company_id
        ) {
            return true;
        }

        // Manager can submit reviews they are the reviewer of
        if ($user->role === UserRole::DepartmentManager) {
            return (int) $user->id === (int) $review->reviewer_id;
        }

        // Employee can submit self-reviews
        if ($user->role === UserRole::Employee && $review->type === 'self_review') {
            $employee = Employee::where('user_id', $user->id)->first();
            return $employee && (int) $employee->id === (int) $review->employee_id;
        }

        return false;
    }

    public function acknowledge(User $user, PerformanceReview $review): bool
    {
        // Only the employee being reviewed can acknowledge
        $employee = Employee::where('user_id', $user->id)->first();

        return $employee && (int) $employee->id === (int) $review->employee_id;
    }

    public function update(User $user, PerformanceReview $review): bool
    {
        return in_array($user->role, [UserRole::CompanyAdmin, UserRole::HrManager])
            && (int) $user->company_id === (int) $review->company_id;
    }

    public function delete(User $user, PerformanceReview $review): bool
    {
        return in_array($user->role, [UserRole::CompanyAdmin, UserRole::HrManager])
            && (int) $user->company_id === (int) $review->company_id;
    }

    /**
     * Check if the given employee is a direct report of the manager user.
     */
    private function isDirectReport(User $user, int $employeeId): bool
    {
        $managerEmployee = Employee::where('user_id', $user->id)->first();

        if (!$managerEmployee) {
            return false;
        }

        return Employee::where('id', $employeeId)
            ->where('manager_id', $managerEmployee->id)
            ->exists();
    }
}

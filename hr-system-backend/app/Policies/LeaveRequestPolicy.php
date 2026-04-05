<?php

namespace App\Policies;

use App\Enums\LeaveRequestStatus;
use App\Enums\UserRole;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\User;

class LeaveRequestPolicy
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

    public function view(User $user, LeaveRequest $leaveRequest): bool
    {
        // CompanyAdmin and HrManager can view leave requests in their company
        if (
            in_array($user->role, [UserRole::CompanyAdmin, UserRole::HrManager])
            && (int) $user->company_id === (int) $leaveRequest->company_id
        ) {
            return true;
        }

        // DepartmentManager can view leave requests for employees in their department
        if ($user->role === UserRole::DepartmentManager) {
            $managerEmployee = Employee::where('user_id', $user->id)->first();
            $requestEmployee = Employee::find($leaveRequest->employee_id);
            if ($managerEmployee && $requestEmployee
                && (int) $managerEmployee->department_id === (int) $requestEmployee->department_id
            ) {
                return true;
            }
        }

        // Employee can view own leave requests
        $employee = Employee::where('user_id', $user->id)->first();

        return $employee && (int) $employee->id === (int) $leaveRequest->employee_id;
    }

    public function create(User $user): bool
    {
        // Any authenticated user with a company can apply for leave
        return in_array($user->role, [
            UserRole::CompanyAdmin,
            UserRole::HrManager,
            UserRole::DepartmentManager,
            UserRole::Employee,
        ]);
    }

    public function approve(User $user, LeaveRequest $leaveRequest): bool
    {
        if (
            in_array($user->role, [UserRole::CompanyAdmin, UserRole::HrManager])
            && (int) $user->company_id === (int) $leaveRequest->company_id
        ) {
            return true;
        }

        // DepartmentManager can approve for their department
        if ($user->role === UserRole::DepartmentManager) {
            $managerEmployee = Employee::where('user_id', $user->id)->first();
            $requestEmployee = Employee::find($leaveRequest->employee_id);
            if ($managerEmployee && $requestEmployee
                && (int) $managerEmployee->department_id === (int) $requestEmployee->department_id
            ) {
                return true;
            }
        }

        return false;
    }

    public function reject(User $user, LeaveRequest $leaveRequest): bool
    {
        return $this->approve($user, $leaveRequest);
    }

    public function cancel(User $user, LeaveRequest $leaveRequest): bool
    {
        // CompanyAdmin/HrManager can cancel for their company
        if (
            in_array($user->role, [UserRole::CompanyAdmin, UserRole::HrManager])
            && (int) $user->company_id === (int) $leaveRequest->company_id
        ) {
            return true;
        }

        // Employee can cancel own pending leave requests
        $employee = Employee::where('user_id', $user->id)->first();

        return $employee
            && (int) $employee->id === (int) $leaveRequest->employee_id
            && $leaveRequest->status === LeaveRequestStatus::Pending;
    }
}

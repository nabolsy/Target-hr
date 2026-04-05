<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\AttendanceRecord;
use App\Models\User;

class AttendancePolicy
{
    public function viewAny(User $user): bool
    {
        return in_array($user->role, [
            UserRole::SuperAdmin,
            UserRole::CompanyAdmin,
            UserRole::HrManager,
            UserRole::DepartmentManager,
        ]);
    }

    public function view(User $user, AttendanceRecord $record): bool
    {
        if ($user->role === UserRole::SuperAdmin) {
            return true;
        }

        if ((int) $user->company_id !== (int) $record->company_id) {
            return false;
        }

        if (in_array($user->role, [UserRole::CompanyAdmin, UserRole::HrManager])) {
            return true;
        }

        return (int) $user->id === (int) $record->employee?->user_id;
    }

    public function checkIn(User $user): bool
    {
        return true;
    }

    public function checkOut(User $user, AttendanceRecord $record): bool
    {
        if (in_array($user->role, [UserRole::SuperAdmin, UserRole::CompanyAdmin, UserRole::HrManager])) {
            return true;
        }

        return (int) $user->company_id === (int) $record->company_id
            && (int) $user->id === (int) $record->employee?->user_id;
    }

    public function requestAdjustment(User $user): bool
    {
        return true;
    }

    public function approveAdjustment(User $user): bool
    {
        return in_array($user->role, [
            UserRole::SuperAdmin,
            UserRole::CompanyAdmin,
            UserRole::HrManager,
        ]);
    }

    public function rejectAdjustment(User $user): bool
    {
        return in_array($user->role, [
            UserRole::SuperAdmin,
            UserRole::CompanyAdmin,
            UserRole::HrManager,
        ]);
    }

    public function create(User $user): bool
    {
        return in_array($user->role, [
            UserRole::SuperAdmin,
            UserRole::CompanyAdmin,
            UserRole::HrManager,
        ]);
    }

    public function update(User $user, AttendanceRecord $record): bool
    {
        if ($user->role === UserRole::SuperAdmin) {
            return true;
        }

        return in_array($user->role, [UserRole::CompanyAdmin, UserRole::HrManager])
            && (int) $user->company_id === (int) $record->company_id;
    }

    public function delete(User $user, AttendanceRecord $record): bool
    {
        if ($user->role === UserRole::SuperAdmin) {
            return true;
        }

        return $user->role === UserRole::CompanyAdmin
            && (int) $user->company_id === (int) $record->company_id;
    }
}

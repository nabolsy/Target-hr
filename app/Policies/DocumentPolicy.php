<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\EmployeeDocument;
use App\Models\User;

class DocumentPolicy
{
    public function viewAny(User $user): bool
    {
        return in_array($user->role, [
            UserRole::SuperAdmin,
            UserRole::CompanyAdmin,
            UserRole::HrManager,
            UserRole::DepartmentManager,
            UserRole::Employee,
        ]);
    }

    public function view(User $user, EmployeeDocument $document): bool
    {
        if ($user->role === UserRole::SuperAdmin) {
            return true;
        }

        if ((int) $user->company_id !== (int) $document->company_id) {
            return false;
        }

        // Employees can only view their own documents
        if ($user->role === UserRole::Employee) {
            return (int) $user->id === (int) $document->employee_id;
        }

        return in_array($user->role, [
            UserRole::CompanyAdmin,
            UserRole::HrManager,
            UserRole::DepartmentManager,
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

    public function update(User $user, EmployeeDocument $document): bool
    {
        if ($user->role === UserRole::SuperAdmin) {
            return true;
        }

        if ((int) $user->company_id !== (int) $document->company_id) {
            return false;
        }

        return in_array($user->role, [
            UserRole::CompanyAdmin,
            UserRole::HrManager,
        ]);
    }

    public function delete(User $user, EmployeeDocument $document): bool
    {
        if ($user->role === UserRole::SuperAdmin) {
            return true;
        }

        if ((int) $user->company_id !== (int) $document->company_id) {
            return false;
        }

        return in_array($user->role, [
            UserRole::CompanyAdmin,
            UserRole::HrManager,
        ]);
    }

    public function download(User $user, EmployeeDocument $document): bool
    {
        return $this->view($user, $document);
    }
}

<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Announcement;
use App\Models\User;

class AnnouncementPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Announcement $announcement): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return (int) $user->company_id === (int) $announcement->company_id;
    }

    public function create(User $user): bool
    {
        return in_array($user->role, [
            UserRole::SuperAdmin,
            UserRole::CompanyAdmin,
            UserRole::HrManager,
        ]);
    }

    public function update(User $user, Announcement $announcement): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if ((int) $user->company_id !== (int) $announcement->company_id) {
            return false;
        }

        return in_array($user->role, [
            UserRole::CompanyAdmin,
            UserRole::HrManager,
        ]) || (int) $user->id === (int) $announcement->created_by;
    }

    public function delete(User $user, Announcement $announcement): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if ((int) $user->company_id !== (int) $announcement->company_id) {
            return false;
        }

        return in_array($user->role, [
            UserRole::CompanyAdmin,
            UserRole::HrManager,
        ]);
    }

    public function publish(User $user, Announcement $announcement): bool
    {
        return $this->update($user, $announcement);
    }

    public function acknowledge(User $user, Announcement $announcement): bool
    {
        return $this->view($user, $announcement);
    }
}

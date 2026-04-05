<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Board;
use App\Models\Employee;
use App\Models\Task;
use App\Models\User;

class TaskPolicy
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

    public function view(User $user, Task $task): bool
    {
        // CompanyAdmin and HrManager can view tasks in their company
        if (
            in_array($user->role, [UserRole::CompanyAdmin, UserRole::HrManager])
            && (int) $user->company_id === (int) $task->company_id
        ) {
            return true;
        }

        // DepartmentManager can view tasks on boards in their department
        if ($user->role === UserRole::DepartmentManager) {
            $managerEmployee = Employee::where('user_id', $user->id)->first();
            if ($managerEmployee) {
                $board = Board::find($task->board_id);
                if ($board && (int) $user->company_id === (int) $board->company_id
                    && ($board->department_id === null || (int) $managerEmployee->department_id === (int) $board->department_id)
                ) {
                    return true;
                }
            }
        }

        // Employee can view tasks assigned to them
        if ($user->role === UserRole::Employee) {
            return $this->isAssignedToTask($user, $task);
        }

        return false;
    }

    public function create(User $user): bool
    {
        return in_array($user->role, [
            UserRole::CompanyAdmin,
            UserRole::HrManager,
            UserRole::DepartmentManager,
            UserRole::Employee,
        ]);
    }

    public function update(User $user, Task $task): bool
    {
        if (
            in_array($user->role, [UserRole::CompanyAdmin, UserRole::HrManager])
            && (int) $user->company_id === (int) $task->company_id
        ) {
            return true;
        }

        // DepartmentManager can update tasks on boards in their department
        if ($user->role === UserRole::DepartmentManager) {
            $managerEmployee = Employee::where('user_id', $user->id)->first();
            if ($managerEmployee) {
                $board = Board::find($task->board_id);
                if ($board && (int) $user->company_id === (int) $board->company_id
                    && ($board->department_id === null || (int) $managerEmployee->department_id === (int) $board->department_id)
                ) {
                    return true;
                }
            }
        }

        // Employee can update tasks assigned to them
        if ($user->role === UserRole::Employee) {
            return $this->isAssignedToTask($user, $task);
        }

        return false;
    }

    public function delete(User $user, Task $task): bool
    {
        if (
            in_array($user->role, [UserRole::CompanyAdmin, UserRole::HrManager])
            && (int) $user->company_id === (int) $task->company_id
        ) {
            return true;
        }

        // DepartmentManager can delete tasks on their department boards
        if ($user->role === UserRole::DepartmentManager) {
            $managerEmployee = Employee::where('user_id', $user->id)->first();
            if ($managerEmployee) {
                $board = Board::find($task->board_id);
                if ($board && $board->department_id
                    && (int) $managerEmployee->department_id === (int) $board->department_id
                ) {
                    return true;
                }
            }
        }

        return false;
    }

    public function move(User $user, Task $task): bool
    {
        return $this->update($user, $task);
    }

    public function assign(User $user, Task $task): bool
    {
        return $this->update($user, $task);
    }

    protected function isAssignedToTask(User $user, Task $task): bool
    {
        $employee = Employee::where('user_id', $user->id)->first();

        return $employee && $task->assignees()->where('employees.id', $employee->id)->exists();
    }
}

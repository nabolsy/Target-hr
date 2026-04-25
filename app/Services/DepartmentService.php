<?php

namespace App\Services;

use App\DTOs\DepartmentDTO;
use App\Enums\DepartmentStatus;
use App\Events\DepartmentCreated;
use App\Exceptions\BusinessException;
use App\Models\Department;
use App\Repositories\Interfaces\DepartmentRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class DepartmentService extends BaseService
{
    public function __construct(
        protected DepartmentRepositoryInterface $departmentRepository,
    ) {
        parent::__construct($departmentRepository);
    }

    public function getByCompany(int $companyId): Collection
    {
        return $this->departmentRepository->getByCompany($companyId);
    }

    public function getActiveByCompany(int $companyId): Collection
    {
        return $this->departmentRepository->getActiveByCompany($companyId);
    }

    public function getRootDepartments(int $companyId): Collection
    {
        return $this->departmentRepository->getRootDepartments($companyId);
    }

    public function getSubDepartments(int $parentId): Collection
    {
        return $this->departmentRepository->getSubDepartments($parentId);
    }

    public function paginateWithFilters(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        return $this->departmentRepository->paginateWithFilters($filters, $perPage);
    }

    public function createDepartment(DepartmentDTO $dto): Department
    {
        return DB::transaction(function () use ($dto) {
            $data = $dto->toArray();

            if ($dto->parentId) {
                $parent = $this->departmentRepository->findOrFail($dto->parentId);

                if ((int) $parent->company_id !== $dto->companyId) {
                    throw new BusinessException('Parent department must belong to the same company.');
                }
            }

            if (! isset($data['status'])) {
                $data['status'] = DepartmentStatus::Active->value;
            }

            $department = $this->departmentRepository->create($data);

            event(new DepartmentCreated($department));

            return $department;
        });
    }

    public function updateDepartment(int $id, DepartmentDTO $dto): Department
    {
        return DB::transaction(function () use ($id, $dto) {
            $department = $this->departmentRepository->findOrFail($id);

            if ($dto->parentId) {
                if ($dto->parentId === $id) {
                    throw new BusinessException('A department cannot be its own parent.');
                }

                $parent = $this->departmentRepository->findOrFail($dto->parentId);

                if ((int) $parent->company_id !== $dto->companyId) {
                    throw new BusinessException('Parent department must belong to the same company.');
                }
            }

            return $this->departmentRepository->update($id, $dto->toArray());
        });
    }

    public function deleteDepartment(int $id): bool
    {
        $department = $this->departmentRepository->findOrFail($id);

        if ($department->children()->count() > 0) {
            throw new BusinessException('Cannot delete a department that has sub-departments. Remove all sub-departments first.');
        }

        if ($department->employees()->count() > 0) {
            throw new BusinessException('Cannot delete a department that has employees. Reassign all employees first.');
        }

        return $this->departmentRepository->delete($id);
    }

    /**
     * Flip a department's status active <-> inactive. Keeps sub-department
     * status untouched (activate/deactivate is intentionally per-node).
     */
    public function toggleStatus(int $id): Department
    {
        $department = $this->departmentRepository->findOrFail($id);

        $next = $department->status === DepartmentStatus::Active
            ? DepartmentStatus::Inactive
            : DepartmentStatus::Active;

        return $this->departmentRepository->update($id, ['status' => $next->value]);
    }

    public function setStatus(int $id, DepartmentStatus $status): Department
    {
        return $this->departmentRepository->update($id, ['status' => $status->value]);
    }

    /**
     * Assign a user as department manager. Validates the user belongs to the
     * same company as the department to prevent cross-tenant manager leaks.
     */
    public function assignManager(int $departmentId, int $userId): Department
    {
        return DB::transaction(function () use ($departmentId, $userId) {
            $department = $this->departmentRepository->findOrFail($departmentId);

            $user = \App\Models\User::findOrFail($userId);
            if ((int) $user->company_id !== (int) $department->company_id) {
                throw new BusinessException('Manager must belong to the same company as the department.');
            }

            return $this->departmentRepository->update($departmentId, ['manager_id' => $userId]);
        });
    }

    public function removeManager(int $departmentId): Department
    {
        return $this->departmentRepository->update($departmentId, ['manager_id' => null]);
    }

    public function getTree(int $companyId): Collection
    {
        return $this->departmentRepository->getTree($companyId);
    }

    /**
     * Per-department stats bundle consumed by DepartmentDetail.jsx. Returns
     * headcount, active employees, pending leave requests for employees in
     * this department, open + overdue tasks assigned to those employees,
     * and Kanban board counts where board.department_id matches.
     *
     * All queries use the existing tenant global scope via BelongsToTenant
     * so cross-company leaks are not possible.
     */
    public function getStats(int $departmentId): array
    {
        $department = $this->departmentRepository->findOrFail($departmentId);

        $employeeIds = \App\Models\Employee::where('department_id', $departmentId)
            ->pluck('id');

        $activeCount = \App\Models\Employee::where('department_id', $departmentId)
            ->where('status', \App\Enums\EmployeeStatus::Active)
            ->count();

        $today = now()->toDateString();
        $endOfWeek = now()->endOfWeek()->toDateString();

        $pendingLeaveCount = \App\Models\LeaveRequest::whereIn('employee_id', $employeeIds)
            ->where('status', 'pending')
            ->count();

        // Approved leaves currently covering today — used for both the
        // on_leave_count tile and the list of names.
        $onLeaveTodayCollection = \App\Models\LeaveRequest::whereIn('employee_id', $employeeIds)
            ->where('status', 'approved')
            ->where('start_date', '<=', $today)
            ->where('end_date', '>=', $today)
            ->with(['employee:id,first_name,last_name,profile_image', 'leaveType:id,name,color'])
            ->get();

        $approvedOnLeaveCount = $onLeaveTodayCollection->count();

        $onLeaveTodayDetail = $onLeaveTodayCollection
            ->map(fn ($lr) => [
                'id' => $lr->id,
                'employee_id' => $lr->employee_id,
                'employee_name' => $lr->employee?->full_name,
                'profile_image' => $lr->employee?->profile_image,
                'leave_type' => $lr->leaveType?->name,
                'leave_type_color' => $lr->leaveType?->color,
                'start_date' => $lr->start_date?->toDateString(),
                'end_date' => $lr->end_date?->toDateString(),
                'total_days' => $lr->total_days,
            ])
            ->values()
            ->all();

        // Approved leaves starting later this week — powers an
        // "upcoming team leaves" widget on the department page.
        $upcomingLeavesWeek = \App\Models\LeaveRequest::whereIn('employee_id', $employeeIds)
            ->where('status', 'approved')
            ->whereDate('start_date', '>', $today)
            ->whereDate('start_date', '<=', $endOfWeek)
            ->with(['employee:id,first_name,last_name,profile_image', 'leaveType:id,name,color'])
            ->orderBy('start_date')
            ->take(20)
            ->get()
            ->map(fn ($lr) => [
                'id' => $lr->id,
                'employee_id' => $lr->employee_id,
                'employee_name' => $lr->employee?->full_name,
                'profile_image' => $lr->employee?->profile_image,
                'leave_type' => $lr->leaveType?->name,
                'leave_type_color' => $lr->leaveType?->color,
                'start_date' => $lr->start_date?->toDateString(),
                'end_date' => $lr->end_date?->toDateString(),
                'total_days' => $lr->total_days,
            ])
            ->values()
            ->all();

        $openTasksCount = \App\Models\Task::where('company_id', $department->company_id)
            ->where('is_archived', false)
            ->whereHas('column', fn ($q) => $q->where('is_done_column', false))
            ->whereHas('assignees', fn ($q) => $q->whereIn('employees.id', $employeeIds))
            ->count();

        $overdueTasksCount = \App\Models\Task::where('company_id', $department->company_id)
            ->where('is_archived', false)
            ->whereNotNull('due_date')
            ->where('due_date', '<', now()->toDateString())
            ->whereHas('column', fn ($q) => $q->where('is_done_column', false))
            ->whereHas('assignees', fn ($q) => $q->whereIn('employees.id', $employeeIds))
            ->count();

        $completedThisWeekCount = \App\Models\Task::where('company_id', $department->company_id)
            ->whereHas('column', fn ($q) => $q->where('is_done_column', true))
            ->whereBetween('updated_at', [now()->startOfWeek(), now()->endOfWeek()])
            ->whereHas('assignees', fn ($q) => $q->whereIn('employees.id', $employeeIds))
            ->count();

        $boardsCount = \App\Models\Board::where('department_id', $departmentId)
            ->where('is_archived', false)
            ->count();

        $archivedBoardsCount = \App\Models\Board::where('department_id', $departmentId)
            ->where('is_archived', true)
            ->count();

        $subdepartmentsCount = $department->children()->count();

        return [
            'department_id' => $departmentId,
            'employees_count' => $employeeIds->count(),
            'active_employees_count' => $activeCount,
            'on_leave_count' => $approvedOnLeaveCount,
            'on_leave_today_detail' => $onLeaveTodayDetail,
            'upcoming_leaves_week' => $upcomingLeavesWeek,
            'pending_leave_count' => $pendingLeaveCount,
            'open_tasks_count' => $openTasksCount,
            'overdue_tasks_count' => $overdueTasksCount,
            'completed_tasks_this_week' => $completedThisWeekCount,
            'boards_count' => $boardsCount,
            'archived_boards_count' => $archivedBoardsCount,
            'subdepartments_count' => $subdepartmentsCount,
        ];
    }
}

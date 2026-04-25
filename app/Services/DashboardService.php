<?php

namespace App\Services;

use App\Enums\CompanyStatus;
use App\Enums\EmployeeStatus;
use App\Enums\LeaveRequestStatus;
use App\Models\AttendanceRecord;
use App\Models\Company;
use App\Models\Department;
use App\Models\Employee;
use App\Models\EmployeeDocument;
use App\Models\LeaveRequest;
use App\Models\Task;
use App\Models\User;
use App\Services\Access\PermissionService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;

class DashboardService
{
    /**
     * Company dashboard with optional scope awareness.
     *
     * When called with a User, the aggregates are filtered by that user's
     * effective scope for each permission key. Company Admin and HR
     * Manager see the whole company (null scope = no filter) and get the
     * full admin widget set. Department Manager / Employee see only their
     * subtree and get the same shape but smaller numbers.
     *
     * Callers who still invoke with only a company_id get the legacy
     * "whole company" behaviour (backwards compatible).
     */
    public function getCompanyDashboard(int $companyId, ?User $user = null): array
    {
        // Cache key includes the user id so different scopes get different
        // cache slices. Admins and no-user calls share the company-wide slice.
        $permissions = $user ? app(PermissionService::class) : null;

        $scope = $permissions?->getScope($user, 'employee.view') ?? 'company';
        $cacheKey = $scope === 'company'
            ? "dashboard:{$companyId}"
            : "dashboard:{$companyId}:user:{$user->id}";

        return Cache::remember($cacheKey, 60, function () use ($companyId, $user, $permissions, $scope) {
            $today = now()->toDateString();
            $startOfWeek = now()->startOfWeek();
            $endOfWeek = now()->endOfWeek();

            // Resolve visible sets once; each aggregate reuses them.
            $visibleEmployeeIds = $permissions
                ? $permissions->visibleEmployeeIds($user, 'employee.view')
                : null;

            $visibleDepartmentIds = $permissions
                ? $permissions->visibleDepartmentIds($user, 'department.view')
                : null;

            // Build a scoped base employee query used throughout.
            $employeeBase = fn () => $this->scopeEmployeeQuery(
                Employee::where('company_id', $companyId),
                $visibleEmployeeIds
            );

            $totalEmployees = $employeeBase()
                ->where('status', EmployeeStatus::Active)
                ->count();

            $attendanceToday = $this->scopeEmployeeLinkedQuery(
                AttendanceRecord::where('company_id', $companyId)->whereDate('date', $today),
                $visibleEmployeeIds
            )->count();

            $pendingLeaveCount = $this->scopeEmployeeLinkedQuery(
                LeaveRequest::where('company_id', $companyId)->where('status', 'pending'),
                $visibleEmployeeIds
            )->count();

            $openTasksCount = $this->scopeTaskQuery(
                Task::where('company_id', $companyId)
                    ->where('is_archived', false)
                    ->whereHas('column', fn ($q) => $q->where('is_done_column', false)),
                $visibleEmployeeIds
            )->count();

            $overdueTasksCount = $this->scopeTaskQuery(
                Task::where('company_id', $companyId)
                    ->where('is_archived', false)
                    ->whereNotNull('due_date')
                    ->where('due_date', '<', $today)
                    ->whereHas('column', fn ($q) => $q->where('is_done_column', false)),
                $visibleEmployeeIds
            )->count();

            // Departments visible to this user.
            $departmentsQuery = Department::where('company_id', $companyId);
            if ($visibleDepartmentIds !== null) {
                if (empty($visibleDepartmentIds)) {
                    $departmentsQuery->whereRaw('1 = 0');
                } else {
                    $departmentsQuery->whereIn('id', $visibleDepartmentIds);
                }
            }

            // Enriched department summary: one row per visible department
            // with the numbers a Company Admin actually uses to triage.
            $departmentsSummary = (clone $departmentsQuery)
                ->withCount([
                    'employees',
                    'employees as active_employees_count' => fn ($q) => $q->where('status', EmployeeStatus::Active),
                ])
                ->with(['manager:id,name'])
                ->orderBy('name')
                ->get()
                ->map(function (Department $dept) use ($visibleEmployeeIds) {
                    $pendingLeaveQuery = LeaveRequest::where('status', 'pending')
                        ->whereHas('employee', fn ($q) => $q->where('department_id', $dept->id));
                    $this->scopeEmployeeLinkedQuery($pendingLeaveQuery, $visibleEmployeeIds);

                    return [
                        'id' => $dept->id,
                        'name' => $dept->name,
                        'name_ar' => $dept->name_ar,
                        'code' => $dept->code,
                        'status' => $dept->status?->value,
                        'manager' => $dept->manager ? [
                            'id' => $dept->manager->id,
                            'name' => $dept->manager->name,
                        ] : null,
                        'employees_count' => (int) $dept->employees_count,
                        'active_employees_count' => (int) $dept->active_employees_count,
                        'pending_leave_count' => $pendingLeaveQuery->count(),
                    ];
                })
                ->values()
                ->all();

            // On Leave Today — detailed list so the dashboard can show
            // names, not just a count. Scoped the same way as every
            // other leave aggregate above.
            $onLeaveTodayQuery = LeaveRequest::where('company_id', $companyId)
                ->where('status', LeaveRequestStatus::Approved)
                ->where('start_date', '<=', $today)
                ->where('end_date', '>=', $today)
                ->with(['employee:id,first_name,last_name,department_id,profile_image', 'leaveType:id,name,color']);
            $this->scopeEmployeeLinkedQuery($onLeaveTodayQuery, $visibleEmployeeIds);

            $onLeaveTodayDetail = $onLeaveTodayQuery->take(20)->get()
                ->map(fn (LeaveRequest $lr) => [
                    'id' => $lr->id,
                    'employee_id' => $lr->employee_id,
                    'employee_name' => $lr->employee?->full_name,
                    'profile_image' => $lr->employee?->profile_image,
                    'department_id' => $lr->employee?->department_id,
                    'leave_type' => $lr->leaveType?->name,
                    'leave_type_color' => $lr->leaveType?->color,
                    'start_date' => $lr->start_date?->toDateString(),
                    'end_date' => $lr->end_date?->toDateString(),
                    'total_days' => $lr->total_days,
                ])
                ->values()
                ->all();

            // Upcoming leaves this week — approved requests that start
            // between tomorrow and the end of the current week.
            $upcomingLeavesQuery = LeaveRequest::where('company_id', $companyId)
                ->where('status', LeaveRequestStatus::Approved)
                ->whereDate('start_date', '>', $today)
                ->whereDate('start_date', '<=', $endOfWeek->toDateString())
                ->with(['employee:id,first_name,last_name,department_id,profile_image', 'leaveType:id,name,color'])
                ->orderBy('start_date');
            $this->scopeEmployeeLinkedQuery($upcomingLeavesQuery, $visibleEmployeeIds);

            $upcomingLeavesWeek = $upcomingLeavesQuery->take(20)->get()
                ->map(fn (LeaveRequest $lr) => [
                    'id' => $lr->id,
                    'employee_id' => $lr->employee_id,
                    'employee_name' => $lr->employee?->full_name,
                    'profile_image' => $lr->employee?->profile_image,
                    'department_id' => $lr->employee?->department_id,
                    'leave_type' => $lr->leaveType?->name,
                    'leave_type_color' => $lr->leaveType?->color,
                    'start_date' => $lr->start_date?->toDateString(),
                    'end_date' => $lr->end_date?->toDateString(),
                    'total_days' => $lr->total_days,
                ])
                ->values()
                ->all();

            // Pending approvals detail — the 10 most recent requests the
            // user can see, ready to drive a clickable list widget.
            $pendingApprovalsQuery = LeaveRequest::where('company_id', $companyId)
                ->where('status', 'pending')
                ->with(['employee:id,first_name,last_name,department_id', 'leaveType:id,name'])
                ->latest();
            $this->scopeEmployeeLinkedQuery($pendingApprovalsQuery, $visibleEmployeeIds);

            $pendingApprovalsDetail = $pendingApprovalsQuery->take(10)->get()
                ->map(fn (LeaveRequest $lr) => [
                    'id' => $lr->id,
                    'employee_id' => $lr->employee_id,
                    'employee_name' => $lr->employee?->full_name,
                    'department_id' => $lr->employee?->department_id,
                    'leave_type' => $lr->leaveType?->name,
                    'start_date' => $lr->start_date?->toDateString(),
                    'end_date' => $lr->end_date?->toDateString(),
                    'total_days' => $lr->total_days,
                    'created_at' => $lr->created_at?->toISOString(),
                ])
                ->values()
                ->all();

            // Overdue tasks detail — top 10 for triage
            $overdueTasksQuery = Task::where('company_id', $companyId)
                ->where('is_archived', false)
                ->whereNotNull('due_date')
                ->where('due_date', '<', $today)
                ->whereHas('column', fn ($q) => $q->where('is_done_column', false))
                ->with(['board:id,name', 'assignees:id,first_name,last_name'])
                ->orderBy('due_date');
            $this->scopeTaskQuery($overdueTasksQuery, $visibleEmployeeIds);

            $overdueTasksDetail = $overdueTasksQuery->take(10)->get()
                ->map(fn (Task $task) => [
                    'id' => $task->id,
                    'title' => $task->title,
                    'board_id' => $task->board_id,
                    'board_name' => $task->board?->name,
                    'due_date' => $task->due_date?->toDateString(),
                    'days_overdue' => $task->due_date ? (int) $task->due_date->diffInDays(now()) : 0,
                    'assignees' => $task->assignees->map(fn ($a) => $a->full_name)->values()->all(),
                ])
                ->values()
                ->all();

            return [
                'scope' => $scope,

                'total_employees' => $totalEmployees,
                'departments_count' => (clone $departmentsQuery)->count(),

                'attendance_today' => $attendanceToday,
                'attendance_rate' => $totalEmployees > 0
                    ? round(($attendanceToday / $totalEmployees) * 100) : 0,

                'employees_on_leave' => $this->scopeEmployeeLinkedQuery(
                    LeaveRequest::where('company_id', $companyId)
                        ->where('status', LeaveRequestStatus::Approved)
                        ->where('start_date', '<=', $today)
                        ->where('end_date', '>=', $today),
                    $visibleEmployeeIds
                )->count(),

                'pending_leave_requests' => $pendingLeaveCount,
                'open_tasks' => $openTasksCount,
                'overdue_tasks' => $overdueTasksCount,

                'tasks_completed_this_week' => $this->scopeTaskQuery(
                    Task::where('company_id', $companyId)
                        ->whereHas('column', fn ($q) => $q->where('is_done_column', true))
                        ->whereBetween('updated_at', [$startOfWeek, $endOfWeek]),
                    $visibleEmployeeIds
                )->count(),

                'document_expiries' => $this->scopeEmployeeLinkedQuery(
                    EmployeeDocument::where('company_id', $companyId)
                        ->whereNotNull('expiry_date')
                        ->where('expiry_date', '>=', $today)
                        ->where('expiry_date', '<=', now()->addDays(30)->toDateString()),
                    $visibleEmployeeIds
                )->count(),

                'new_this_month' => $employeeBase()
                    ->whereMonth('created_at', now()->month)
                    ->whereYear('created_at', now()->year)
                    ->count(),

                // Chart data — one row per visible department. Returns
                // both `name` (English) and `name_ar` so the frontend can
                // pick the right label for the active locale instead of
                // always rendering English on the Arabic build.
                'employees_by_department' => (clone $departmentsQuery)
                    ->withCount('employees')
                    ->get(['id', 'name', 'name_ar'])
                    ->map(fn ($d) => [
                        'name' => $d->name,
                        'name_ar' => $d->name_ar,
                        'count' => $d->employees_count,
                    ])
                    ->values()
                    ->all(),

                // New: admin widgets
                'departments_summary' => $departmentsSummary,
                'pending_approvals_detail' => $pendingApprovalsDetail,
                'overdue_tasks_detail' => $overdueTasksDetail,
                'on_leave_today_detail' => $onLeaveTodayDetail,
                'upcoming_leaves_week' => $upcomingLeavesWeek,

                // Recent employees (scoped)
                'recent_employees' => $employeeBase()
                    ->with(['department:id,name', 'designation:id,name'])
                    ->latest()->take(5)
                    ->get(['id', 'first_name', 'last_name', 'department_id', 'designation_id', 'status', 'created_at'])
                    ->values()
                    ->toArray(),

                // Recent leave requests (scoped)
                'recent_leave_requests' => $this->scopeEmployeeLinkedQuery(
                    LeaveRequest::where('company_id', $companyId)
                        ->with(['employee:id,first_name,last_name', 'leaveType:id,name'])
                        ->latest()
                        ->take(5),
                    $visibleEmployeeIds
                )
                    ->get(['id', 'employee_id', 'leave_type_id', 'start_date', 'end_date', 'total_days', 'status', 'created_at'])
                    ->values()
                    ->toArray(),
            ];
        });
    }

    public function getSuperAdminDashboard(): array
    {
        return Cache::remember('dashboard:super_admin', 60, function () {
            return [
                'total_companies' => Company::count(),
                'total_employees' => Employee::where('status', EmployeeStatus::Active)->count(),
                'active_companies' => Company::where('status', CompanyStatus::Active)->count(),
            ];
        });
    }

    /**
     * Restrict an Employee query to a set of visible employee IDs.
     * null → no filter (company scope). [] → deny.
     */
    private function scopeEmployeeQuery(Builder $query, ?array $visibleEmployeeIds): Builder
    {
        if ($visibleEmployeeIds === null) {
            return $query;
        }
        if (empty($visibleEmployeeIds)) {
            return $query->whereRaw('1 = 0');
        }

        return $query->whereIn('id', $visibleEmployeeIds);
    }

    /**
     * Restrict a query that has an employee_id column (Leave, Attendance,
     * Document) to a visible set.
     */
    private function scopeEmployeeLinkedQuery(Builder $query, ?array $visibleEmployeeIds): Builder
    {
        if ($visibleEmployeeIds === null) {
            return $query;
        }
        if (empty($visibleEmployeeIds)) {
            return $query->whereRaw('1 = 0');
        }

        return $query->whereIn('employee_id', $visibleEmployeeIds);
    }

    /**
     * Restrict a Task query by the intersection of its assignees with the
     * visible-employee set. Tasks with no assignees are excluded from
     * non-company-scope views.
     */
    private function scopeTaskQuery(Builder $query, ?array $visibleEmployeeIds): Builder
    {
        if ($visibleEmployeeIds === null) {
            return $query;
        }
        if (empty($visibleEmployeeIds)) {
            return $query->whereRaw('1 = 0');
        }

        return $query->whereHas('assignees', fn ($q) => $q->whereIn('employees.id', $visibleEmployeeIds));
    }
}

<?php

namespace App\Services;

use App\Enums\AttendanceStatus;
use App\Enums\LeaveRequestStatus;
use App\Models\AttendanceRecord;
use App\Models\Employee;
use App\Models\EmployeeDocument;
use App\Models\LeaveBalance;
use App\Models\LeaveRequest;
use App\Models\Task;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Report aggregations.
 *
 * Every method accepts an optional `$visibleEmployeeIds` parameter (null
 * means "no restriction" / company scope, [] means deny-all). The
 * controller passes this in from PermissionService::visibleEmployeeIds,
 * so a Department Manager running /reports/attendance only sees their
 * subtree's numbers; Company Admin sees the whole company.
 *
 * The scope filter is applied BEFORE any user-supplied filter so admins
 * cannot "unlock" a broader view by passing a department_id they don't
 * already have access to — the AND semantics guarantee the intersection.
 */
class ReportService
{
    public function employeeReport(int $companyId, array $filters = [], ?array $visibleEmployeeIds = null): Collection
    {
        $query = Employee::where('company_id', $companyId)
            ->with(['department', 'designation']);

        $this->applyEmployeeScope($query, $visibleEmployeeIds);

        if (! empty($filters['department_id'])) {
            $query->where('department_id', $filters['department_id']);
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['employment_type'])) {
            $query->where('employment_type', $filters['employment_type']);
        }

        if (! empty($filters['start_date'])) {
            $query->where('join_date', '>=', $filters['start_date']);
        }

        if (! empty($filters['end_date'])) {
            $query->where('join_date', '<=', $filters['end_date']);
        }

        return $query->get()->map(fn (Employee $employee) => [
            'id' => $employee->id,
            'employee_id_number' => $employee->employee_id_number,
            'full_name' => $employee->full_name,
            'email' => $employee->email,
            'department' => $employee->department?->name,
            'designation' => $employee->designation?->name,
            'employment_type' => $employee->employment_type->value,
            'status' => $employee->status->value,
            'join_date' => $employee->join_date?->toDateString(),
        ]);
    }

    public function attendanceReport(int $companyId, array $filters = [], ?array $visibleEmployeeIds = null): Collection
    {
        $startDate = $filters['start_date'] ?? now()->startOfMonth()->toDateString();
        $endDate = $filters['end_date'] ?? now()->toDateString();

        $query = AttendanceRecord::where('company_id', $companyId)
            ->whereBetween('date', [$startDate, $endDate])
            ->with('employee');

        if ($visibleEmployeeIds !== null) {
            if (empty($visibleEmployeeIds)) {
                return collect();
            }
            $query->whereIn('employee_id', $visibleEmployeeIds);
        }

        if (! empty($filters['department_id'])) {
            $query->whereHas('employee', fn ($q) => $q->where('department_id', $filters['department_id']));
        }

        $records = $query->get();

        return $records->groupBy('employee_id')->map(function ($employeeRecords) {
            $employee = $employeeRecords->first()->employee;

            return [
                'employee_id' => $employee->id,
                'employee_name' => $employee->full_name,
                'present_days' => $employeeRecords->where('status', AttendanceStatus::Present)->count()
                    + $employeeRecords->where('status', AttendanceStatus::Remote)->count(),
                'absent_days' => $employeeRecords->where('status', AttendanceStatus::Absent)->count(),
                'late_days' => $employeeRecords->where('status', AttendanceStatus::Late)->count(),
                'leave_days' => $employeeRecords->where('status', AttendanceStatus::OnLeave)->count(),
                'total_worked_hours' => round($employeeRecords->sum('worked_hours'), 2),
            ];
        })->values();
    }

    /**
     * Leave analytics — returns a compound structure with four
     * aggregations plus the legacy per-employee balance breakdown.
     *
     *   by_employee    — employee rows with their leave balances for the year
     *   by_department  — total approved leave days per department
     *   by_type        — total approved leave days per leave type
     *   monthly_trend  — approved leave days per month (12 buckets)
     *   top_takers     — top 10 employees by approved leave days
     *
     * Filters:
     *   year           — defaults to current year
     *   department_id  — scope all aggregates to a single department
     *   leave_type_id  — scope all aggregates to a single leave type
     *   start_date / end_date — override the date window for the
     *                           approval-based aggregates (monthly_trend,
     *                           by_department, by_type, top_takers).
     *                           by_employee is still year-based.
     */
    public function leaveReport(int $companyId, array $filters = [], ?array $visibleEmployeeIds = null): array
    {
        $year = (int) ($filters['year'] ?? now()->year);
        $departmentId = $filters['department_id'] ?? null;
        $leaveTypeId  = $filters['leave_type_id'] ?? null;
        $startDate    = $filters['start_date'] ?? null;
        $endDate      = $filters['end_date'] ?? null;

        // Short-circuit: the caller has no visible employees → empty everything.
        if (is_array($visibleEmployeeIds) && empty($visibleEmployeeIds)) {
            return [
                'year' => $year,
                'by_employee' => [],
                'by_department' => [],
                'by_type' => [],
                'monthly_trend' => [],
                'top_takers' => [],
            ];
        }

        // ── by_employee — the legacy "balances per employee" rollup ──
        $balanceQuery = LeaveBalance::where('company_id', $companyId)
            ->where('year', $year)
            ->with(['employee:id,first_name,last_name,department_id', 'leaveType:id,name,color']);

        if ($visibleEmployeeIds !== null) {
            $balanceQuery->whereIn('employee_id', $visibleEmployeeIds);
        }
        if ($departmentId) {
            $balanceQuery->whereHas('employee', fn ($q) => $q->where('department_id', $departmentId));
        }
        if ($leaveTypeId) {
            $balanceQuery->where('leave_type_id', $leaveTypeId);
        }

        $byEmployee = $balanceQuery->get()->groupBy('employee_id')->map(function ($balances) {
            $employee = $balances->first()->employee;

            return [
                'employee_id' => $employee->id,
                'employee_name' => $employee->full_name,
                'department_id' => $employee->department_id,
                'leave_balances' => $balances->map(fn ($balance) => [
                    'leave_type' => $balance->leaveType?->name,
                    'leave_type_color' => $balance->leaveType?->color,
                    'total_days' => (float) $balance->total_days,
                    'used_days' => (float) $balance->used_days,
                    'remaining_days' => (float) $balance->remaining_days,
                ])->values()->all(),
            ];
        })->values()->all();

        // ── Build the approved-leave base query for the chart aggregates ──
        $approvedBase = LeaveRequest::where('company_id', $companyId)
            ->where('status', 'approved');

        if ($visibleEmployeeIds !== null) {
            $approvedBase->whereIn('employee_id', $visibleEmployeeIds);
        }
        if ($leaveTypeId) {
            $approvedBase->where('leave_type_id', $leaveTypeId);
        }
        if ($departmentId) {
            $approvedBase->whereHas('employee', fn ($q) => $q->where('department_id', $departmentId));
        }

        // Date window: explicit start/end wins; otherwise the full year.
        $windowStart = $startDate ?: "{$year}-01-01";
        $windowEnd   = $endDate   ?: "{$year}-12-31";
        $approvedBase->whereDate('start_date', '>=', $windowStart)
            ->whereDate('start_date', '<=', $windowEnd);

        // We load everything once and roll up in PHP so we avoid four
        // near-identical SQL passes. Report queries are low-volume and
        // live behind a permission gate, so memory isn't a concern.
        $requests = (clone $approvedBase)
            ->with(['employee:id,first_name,last_name,department_id', 'leaveType:id,name,color', 'employee.department:id,name'])
            ->get();

        // by_department
        $byDepartment = $requests
            ->groupBy(fn ($lr) => $lr->employee?->department?->id ?? 0)
            ->map(function ($group) {
                $department = $group->first()->employee?->department;

                return [
                    'department_id' => $department?->id,
                    'department_name' => $department?->name ?? 'Unassigned',
                    'total_days' => round($group->sum('total_days'), 1),
                    'request_count' => $group->count(),
                ];
            })
            ->sortByDesc('total_days')
            ->values()
            ->all();

        // by_type
        $byType = $requests
            ->groupBy(fn ($lr) => $lr->leave_type_id)
            ->map(function ($group) {
                $type = $group->first()->leaveType;

                return [
                    'leave_type_id' => $type?->id,
                    'leave_type' => $type?->name ?? 'Unknown',
                    'color' => $type?->color,
                    'total_days' => round($group->sum('total_days'), 1),
                    'request_count' => $group->count(),
                ];
            })
            ->sortByDesc('total_days')
            ->values()
            ->all();

        // monthly_trend — 12 buckets keyed by month number, always
        // pre-seeded so chart libraries don't fight missing months.
        $monthlyBuckets = collect(range(1, 12))->mapWithKeys(fn ($m) => [$m => [
            'month' => $m,
            'label' => \DateTime::createFromFormat('!m', (string) $m)?->format('M') ?? (string) $m,
            'total_days' => 0.0,
            'request_count' => 0,
        ]]);

        foreach ($requests as $lr) {
            if (! $lr->start_date) continue;
            $month = (int) $lr->start_date->format('n');
            if (! isset($monthlyBuckets[$month])) continue;
            $bucket = $monthlyBuckets[$month];
            $bucket['total_days'] = round($bucket['total_days'] + (float) $lr->total_days, 1);
            $bucket['request_count'] += 1;
            $monthlyBuckets[$month] = $bucket;
        }

        $monthlyTrend = $monthlyBuckets->values()->all();

        // top_takers — 10 employees with the highest approved day totals.
        $topTakers = $requests
            ->groupBy('employee_id')
            ->map(function ($group) {
                $employee = $group->first()->employee;

                return [
                    'employee_id' => $employee?->id,
                    'employee_name' => $employee?->full_name,
                    'department_name' => $employee?->department?->name,
                    'total_days' => round($group->sum('total_days'), 1),
                    'request_count' => $group->count(),
                ];
            })
            ->sortByDesc('total_days')
            ->take(10)
            ->values()
            ->all();

        return [
            'year' => $year,
            'by_employee' => $byEmployee,
            'by_department' => $byDepartment,
            'by_type' => $byType,
            'monthly_trend' => $monthlyTrend,
            'top_takers' => $topTakers,
        ];
    }

    public function taskPerformanceReport(int $companyId, array $filters = [], ?array $visibleEmployeeIds = null): Collection
    {
        $query = Employee::where('company_id', $companyId)
            ->active()
            ->with(['tasks' => function ($q) use ($filters) {
                if (! empty($filters['start_date'])) {
                    $q->where('created_at', '>=', $filters['start_date']);
                }
                if (! empty($filters['end_date'])) {
                    $q->where('created_at', '<=', $filters['end_date'] . ' 23:59:59');
                }
            }, 'tasks.column']);

        $this->applyEmployeeScope($query, $visibleEmployeeIds);

        if (! empty($filters['department_id'])) {
            $query->where('department_id', $filters['department_id']);
        }

        return $query->get()->map(function (Employee $employee) {
            $tasks = $employee->tasks;
            $completedTasks = $tasks->filter(fn ($task) => $task->column?->is_done_column);

            return [
                'employee_id' => $employee->id,
                'employee_name' => $employee->full_name,
                'total_assigned' => $tasks->count(),
                'completed' => $completedTasks->count(),
                'in_progress' => $tasks->count() - $completedTasks->count(),
                'completion_rate' => $tasks->count() > 0
                    ? round(($completedTasks->count() / $tasks->count()) * 100, 1)
                    : 0,
            ];
        });
    }

    public function overdueTaskReport(int $companyId, ?array $visibleEmployeeIds = null): Collection
    {
        $query = Task::where('company_id', $companyId)
            ->where('is_archived', false)
            ->whereNotNull('due_date')
            ->where('due_date', '<', now()->toDateString())
            ->whereHas('column', fn ($q) => $q->where('is_done_column', false))
            ->with(['assignees', 'column', 'board']);

        // Tasks are scoped by assignee intersection: show overdue tasks
        // whose assignee set overlaps with the visible employees.
        if ($visibleEmployeeIds !== null) {
            if (empty($visibleEmployeeIds)) {
                return collect();
            }
            $query->whereHas('assignees', fn ($q) => $q->whereIn('employees.id', $visibleEmployeeIds));
        }

        return $query->get()->map(fn (Task $task) => [
            'task_id' => $task->id,
            'title' => $task->title,
            'board' => $task->board?->name,
            'column' => $task->column?->name,
            'priority' => $task->priority?->value,
            'due_date' => $task->due_date->toDateString(),
            'days_overdue' => $task->due_date->diffInDays(now()),
            'assignees' => $task->assignees->map(fn ($a) => $a->full_name),
        ]);
    }

    public function documentExpiryReport(int $companyId, int $days = 30, ?array $visibleEmployeeIds = null): Collection
    {
        $today = now()->toDateString();
        $cutoff = now()->addDays($days)->toDateString();

        $query = EmployeeDocument::where('company_id', $companyId)
            ->whereNotNull('expiry_date')
            ->where('expiry_date', '>=', $today)
            ->where('expiry_date', '<=', $cutoff)
            ->with('employee')
            ->orderBy('expiry_date');

        if ($visibleEmployeeIds !== null) {
            if (empty($visibleEmployeeIds)) {
                return collect();
            }
            $query->whereIn('employee_id', $visibleEmployeeIds);
        }

        return $query->get()->map(fn (EmployeeDocument $doc) => [
            'document_id' => $doc->id,
            'title' => $doc->title,
            'type' => $doc->type?->value,
            'employee_id' => $doc->employee_id,
            'employee_name' => $doc->employee?->name ?? null,
            'expiry_date' => $doc->expiry_date->toDateString(),
            'days_until_expiry' => now()->diffInDays($doc->expiry_date, false),
        ]);
    }

    /**
     * Shared helper: apply the visible employee ID filter to an Employee
     * query. Used by employeeReport + taskPerformanceReport which both
     * query the Employee model directly (employee.id vs employee_id).
     */
    private function applyEmployeeScope(Builder $query, ?array $visibleEmployeeIds): void
    {
        if ($visibleEmployeeIds === null) {
            return;
        }

        if (empty($visibleEmployeeIds)) {
            $query->whereRaw('1 = 0');

            return;
        }

        $query->whereIn('id', $visibleEmployeeIds);
    }
}

<?php

namespace App\Services;

use App\Enums\AttendanceStatus;
use App\Enums\LeaveRequestStatus;
use App\Models\AttendanceRecord;
use App\Models\Employee;
use App\Models\EmployeeDocument;
use App\Models\LeaveBalance;
use App\Models\Task;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ReportService
{
    public function employeeReport(int $companyId, array $filters = []): Collection
    {
        $query = Employee::where('company_id', $companyId)
            ->with(['department', 'designation']);

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

    public function attendanceReport(int $companyId, array $filters = []): Collection
    {
        $startDate = $filters['start_date'] ?? now()->startOfMonth()->toDateString();
        $endDate = $filters['end_date'] ?? now()->toDateString();

        $query = AttendanceRecord::where('company_id', $companyId)
            ->whereBetween('date', [$startDate, $endDate])
            ->with('employee');

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

    public function leaveReport(int $companyId, array $filters = []): Collection
    {
        $year = $filters['year'] ?? now()->year;

        $query = LeaveBalance::where('company_id', $companyId)
            ->where('year', $year)
            ->with(['employee', 'leaveType']);

        if (! empty($filters['department_id'])) {
            $query->whereHas('employee', fn ($q) => $q->where('department_id', $filters['department_id']));
        }

        return $query->get()->groupBy('employee_id')->map(function ($balances) {
            $employee = $balances->first()->employee;

            return [
                'employee_id' => $employee->id,
                'employee_name' => $employee->full_name,
                'leave_balances' => $balances->map(fn ($balance) => [
                    'leave_type' => $balance->leaveType?->name,
                    'total_days' => $balance->total_days,
                    'used_days' => $balance->used_days,
                    'remaining_days' => $balance->remaining_days,
                ]),
            ];
        })->values();
    }

    public function taskPerformanceReport(int $companyId, array $filters = []): Collection
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

    public function overdueTaskReport(int $companyId): Collection
    {
        return Task::where('company_id', $companyId)
            ->where('is_archived', false)
            ->whereNotNull('due_date')
            ->where('due_date', '<', now()->toDateString())
            ->whereHas('column', fn ($q) => $q->where('is_done_column', false))
            ->with(['assignees', 'column', 'board'])
            ->get()
            ->map(fn (Task $task) => [
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

    public function documentExpiryReport(int $companyId, int $days = 30): Collection
    {
        $today = now()->toDateString();
        $cutoff = now()->addDays($days)->toDateString();

        return EmployeeDocument::where('company_id', $companyId)
            ->whereNotNull('expiry_date')
            ->where('expiry_date', '>=', $today)
            ->where('expiry_date', '<=', $cutoff)
            ->with('employee')
            ->orderBy('expiry_date')
            ->get()
            ->map(fn (EmployeeDocument $doc) => [
                'document_id' => $doc->id,
                'title' => $doc->title,
                'type' => $doc->type?->value,
                'employee_id' => $doc->employee_id,
                'employee_name' => $doc->employee?->name ?? null,
                'expiry_date' => $doc->expiry_date->toDateString(),
                'days_until_expiry' => now()->diffInDays($doc->expiry_date, false),
            ]);
    }
}

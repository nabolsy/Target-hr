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
use Illuminate\Support\Facades\Cache;

class DashboardService
{
    public function getCompanyDashboard(int $companyId): array
    {
        return Cache::remember('dashboard:' . $companyId, 300, function () use ($companyId) {
            $today = now()->toDateString();
            $startOfWeek = now()->startOfWeek();
            $endOfWeek = now()->endOfWeek();

            return [
                'total_employees' => Employee::where('company_id', $companyId)
                    ->where('status', EmployeeStatus::Active)
                    ->count(),

                'attendance_today' => AttendanceRecord::where('company_id', $companyId)
                    ->whereDate('date', $today)
                    ->count(),

                'employees_on_leave' => LeaveRequest::where('company_id', $companyId)
                    ->where('status', LeaveRequestStatus::Approved)
                    ->where('start_date', '<=', $today)
                    ->where('end_date', '>=', $today)
                    ->count(),

                'open_tasks' => Task::where('company_id', $companyId)
                    ->where('is_archived', false)
                    ->whereHas('column', fn ($q) => $q->where('is_done_column', false))
                    ->count(),

                'overdue_tasks' => Task::where('company_id', $companyId)
                    ->where('is_archived', false)
                    ->whereNotNull('due_date')
                    ->where('due_date', '<', $today)
                    ->whereHas('column', fn ($q) => $q->where('is_done_column', false))
                    ->count(),

                'tasks_completed_this_week' => Task::where('company_id', $companyId)
                    ->whereHas('column', fn ($q) => $q->where('is_done_column', true))
                    ->whereBetween('updated_at', [$startOfWeek, $endOfWeek])
                    ->count(),

                'document_expiries_30_days' => EmployeeDocument::where('company_id', $companyId)
                    ->whereNotNull('expiry_date')
                    ->where('expiry_date', '>=', $today)
                    ->where('expiry_date', '<=', now()->addDays(30)->toDateString())
                    ->count(),

                'departments_count' => Department::where('company_id', $companyId)->count(),
            ];
        });
    }

    public function getSuperAdminDashboard(): array
    {
        return Cache::remember('dashboard:super_admin', 300, function () {
            return [
                'total_companies' => Company::count(),
                'total_employees' => Employee::where('status', EmployeeStatus::Active)->count(),
                'active_companies' => Company::where('status', CompanyStatus::Active)->count(),
            ];
        });
    }
}

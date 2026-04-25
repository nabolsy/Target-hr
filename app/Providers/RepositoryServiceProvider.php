<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

// Company
use App\Repositories\Interfaces\CompanyRepositoryInterface;
use App\Repositories\Eloquent\CompanyRepository;

// Department
use App\Repositories\Interfaces\DepartmentRepositoryInterface;
use App\Repositories\Eloquent\DepartmentRepository;

// Designation
use App\Repositories\Interfaces\DesignationRepositoryInterface;
use App\Repositories\Eloquent\DesignationRepository;

// Attendance
use App\Repositories\Interfaces\AttendanceRepositoryInterface;
use App\Repositories\Eloquent\AttendanceRepository;

// Shift
use App\Repositories\Interfaces\ShiftRepositoryInterface;
use App\Repositories\Eloquent\ShiftRepository;

// Document
use App\Repositories\Interfaces\DocumentRepositoryInterface;
use App\Repositories\Eloquent\DocumentRepository;

// Announcement
use App\Repositories\Interfaces\AnnouncementRepositoryInterface;
use App\Repositories\Eloquent\AnnouncementRepository;

// AuditLog
use App\Repositories\Interfaces\AuditLogRepositoryInterface;
use App\Repositories\Eloquent\AuditLogRepository;

// Salary Structure
use App\Repositories\Interfaces\SalaryStructureRepositoryInterface;
use App\Repositories\Eloquent\SalaryStructureRepository;

// Payroll
use App\Repositories\Interfaces\PayrollRepositoryInterface;
use App\Repositories\Eloquent\PayrollRepository;

// Company Setting
use App\Repositories\Interfaces\CompanySettingRepositoryInterface;
use App\Repositories\Eloquent\CompanySettingRepository;

// Company Branch
use App\Repositories\Interfaces\CompanyBranchRepositoryInterface;
use App\Repositories\Eloquent\CompanyBranchRepository;

class RepositoryServiceProvider extends ServiceProvider
{
    /**
     * All repository interface-to-implementation bindings.
     * Add new module bindings here as modules are built.
     */
    protected array $repositories = [
        CompanyRepositoryInterface::class => CompanyRepository::class,
        DepartmentRepositoryInterface::class => DepartmentRepository::class,
        DesignationRepositoryInterface::class => DesignationRepository::class,
        \App\Repositories\Interfaces\EmployeeRepositoryInterface::class => \App\Repositories\Eloquent\EmployeeRepository::class,
        \App\Repositories\Interfaces\LeaveRequestRepositoryInterface::class => \App\Repositories\Eloquent\LeaveRequestRepository::class,
        \App\Repositories\Interfaces\LeaveTypeRepositoryInterface::class => \App\Repositories\Eloquent\LeaveTypeRepository::class,
        \App\Repositories\Interfaces\LeaveBalanceRepositoryInterface::class => \App\Repositories\Eloquent\LeaveBalanceRepository::class,
        \App\Repositories\Interfaces\HolidayRepositoryInterface::class => \App\Repositories\Eloquent\HolidayRepository::class,
        AttendanceRepositoryInterface::class => AttendanceRepository::class,
        ShiftRepositoryInterface::class => ShiftRepository::class,
        DocumentRepositoryInterface::class => DocumentRepository::class,
        AnnouncementRepositoryInterface::class => AnnouncementRepository::class,
        \App\Repositories\Interfaces\PerformanceReviewRepositoryInterface::class => \App\Repositories\Eloquent\PerformanceReviewRepository::class,
        \App\Repositories\Interfaces\ReviewCycleRepositoryInterface::class => \App\Repositories\Eloquent\ReviewCycleRepository::class,
        \App\Repositories\Interfaces\GoalRepositoryInterface::class => \App\Repositories\Eloquent\GoalRepository::class,
        \App\Repositories\Interfaces\JobOpeningRepositoryInterface::class => \App\Repositories\Eloquent\JobOpeningRepository::class,
        \App\Repositories\Interfaces\CandidateRepositoryInterface::class => \App\Repositories\Eloquent\CandidateRepository::class,
        \App\Repositories\Interfaces\OnboardingTemplateRepositoryInterface::class => \App\Repositories\Eloquent\OnboardingTemplateRepository::class,
        \App\Repositories\Interfaces\OnboardingChecklistRepositoryInterface::class => \App\Repositories\Eloquent\OnboardingChecklistRepository::class,
        \App\Repositories\Interfaces\AssetRepositoryInterface::class => \App\Repositories\Eloquent\AssetRepository::class,
        \App\Repositories\Interfaces\BoardRepositoryInterface::class => \App\Repositories\Eloquent\BoardRepository::class,
        \App\Repositories\Interfaces\TaskRepositoryInterface::class => \App\Repositories\Eloquent\TaskRepository::class,
        AuditLogRepositoryInterface::class => AuditLogRepository::class,
        SalaryStructureRepositoryInterface::class => SalaryStructureRepository::class,
        PayrollRepositoryInterface::class => PayrollRepository::class,
        CompanySettingRepositoryInterface::class => CompanySettingRepository::class,
        CompanyBranchRepositoryInterface::class => CompanyBranchRepository::class,
    ];

    public function register(): void
    {
        foreach ($this->repositories as $interface => $implementation) {
            $this->app->bind($interface, $implementation);
        }
    }

    public function boot(): void
    {
        //
    }
}

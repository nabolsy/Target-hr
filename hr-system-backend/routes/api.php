<?php

use App\Http\Controllers\Api\V1\AnnouncementController;
use App\Http\Controllers\Api\V1\AssetController;
use App\Http\Controllers\Api\V1\AttendanceController;
use App\Http\Controllers\Api\V1\AuditLogController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\BoardController;
use App\Http\Controllers\Api\V1\CandidateController;
use App\Http\Controllers\Api\V1\CompanyBranchController;
use App\Http\Controllers\Api\V1\CompanyController;
use App\Http\Controllers\Api\V1\CompanySettingController;
use App\Http\Controllers\Api\V1\DashboardController;
use App\Http\Controllers\Api\V1\DepartmentController;
use App\Http\Controllers\Api\V1\DesignationController;
use App\Http\Controllers\Api\V1\DocumentController;
use App\Http\Controllers\Api\V1\EmployeeController;
use App\Http\Controllers\Api\V1\GoalController;
use App\Http\Controllers\Api\V1\HolidayController;
use App\Http\Controllers\Api\V1\InterviewController;
use App\Http\Controllers\Api\V1\JobOpeningController;
use App\Http\Controllers\Api\V1\LeaveRequestController;
use App\Http\Controllers\Api\V1\LeaveTypeController;
use App\Http\Controllers\Api\V1\NotificationController;
use App\Http\Controllers\Api\V1\OnboardingChecklistController;
use App\Http\Controllers\Api\V1\OnboardingTemplateController;
use App\Http\Controllers\Api\V1\PayrollController;
use App\Http\Controllers\Api\V1\PerformanceReviewController;
use App\Http\Controllers\Api\V1\ReportController;
use App\Http\Controllers\Api\V1\ReviewCycleController;
use App\Http\Controllers\Api\V1\SalaryStructureController;
use App\Http\Controllers\Api\V1\ShiftController;
use App\Http\Controllers\Api\V1\TaskController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes — /api/v1
|--------------------------------------------------------------------------
*/

// ── Auth (public) ────────────────────────────────────────────────────
Route::post('auth/register', [AuthController::class, 'register']);
Route::post('auth/login', [AuthController::class, 'login']);
Route::post('auth/forgot-password', [\App\Http\Controllers\Api\V1\ForgotPasswordController::class, 'sendResetLink']);
Route::post('auth/reset-password', [\App\Http\Controllers\Api\V1\ForgotPasswordController::class, 'resetPassword']);

// ── Protected routes ─────────────────────────────────────────────────
Route::middleware(['auth:sanctum'])->group(function () {

    // Auth
    Route::post('logout', [AuthController::class, 'logout']);
    Route::post('auth/logout', [AuthController::class, 'logout']);
    Route::get('me', [AuthController::class, 'me']);
    Route::get('auth/me', [AuthController::class, 'me']);

    // Dashboard
    Route::get('dashboard', [DashboardController::class, 'companyDashboard']);
    Route::get('dashboard/super-admin', [DashboardController::class, 'superAdminDashboard']);

    // User Account Management
    Route::get('users', [\App\Http\Controllers\Api\V1\UserController::class, 'index']);
    Route::post('users', [\App\Http\Controllers\Api\V1\UserController::class, 'store']);
    Route::put('users/{user}', [\App\Http\Controllers\Api\V1\UserController::class, 'update']);
    Route::post('users/{user}/reset-password', [\App\Http\Controllers\Api\V1\UserController::class, 'resetPassword']);
    Route::delete('users/{user}', [\App\Http\Controllers\Api\V1\UserController::class, 'destroy']);

    // Roles & Permissions (System Management)
    Route::get('permissions', [\App\Http\Controllers\Api\V1\PermissionController::class, 'index']);
    Route::get('roles', [\App\Http\Controllers\Api\V1\RoleController::class, 'index']);
    Route::post('roles', [\App\Http\Controllers\Api\V1\RoleController::class, 'store']);
    Route::put('roles/{role}', [\App\Http\Controllers\Api\V1\RoleController::class, 'update']);
    Route::delete('roles/{role}', [\App\Http\Controllers\Api\V1\RoleController::class, 'destroy']);

    // Companies
    Route::apiResource('companies', CompanyController::class);
    Route::patch('companies/{company}/status', [CompanyController::class, 'updateStatus'])
        ->name('companies.update-status');

    // Company Settings
    Route::get('settings', [CompanySettingController::class, 'show']);
    Route::put('settings', [CompanySettingController::class, 'update']);

    // Company Branches
    Route::apiResource('branches', CompanyBranchController::class);

    // Departments & Designations
    // Departments — tree + manager + status + stats endpoints registered
    // before apiResource so /departments/tree and /departments/{id}/stats
    // are not swallowed by the generic {department} param binding.
    Route::get('departments/tree', [DepartmentController::class, 'tree'])->name('departments.tree');
    Route::get('departments/{department}/stats', [DepartmentController::class, 'stats'])->name('departments.stats');
    Route::patch('departments/{department}/status', [DepartmentController::class, 'toggleStatus'])->name('departments.toggle-status');
    Route::post('departments/{department}/manager', [DepartmentController::class, 'assignManager'])->name('departments.assign-manager');
    Route::delete('departments/{department}/manager', [DepartmentController::class, 'removeManager'])->name('departments.remove-manager');
    Route::apiResource('departments', DepartmentController::class);
    Route::apiResource('designations', DesignationController::class);

    // Employees
    Route::patch('employees/{employee}/status', [EmployeeController::class, 'updateStatus'])
        ->name('employees.update-status');
    // Peek-ahead helper MUST register before the apiResource so it
    // isn't swallowed by the {employee} wildcard.
    Route::get('employees/next-id-number', [EmployeeController::class, 'nextIdNumber'])
        ->name('employees.next-id-number');
    Route::apiResource('employees', EmployeeController::class);

    // Attendance
    Route::post('attendance/check-in', [AttendanceController::class, 'checkIn']);
    Route::post('attendance/check-out', [AttendanceController::class, 'checkOut']);
    Route::get('attendance/monthly-report', [AttendanceController::class, 'monthlyReport']);
    Route::post('attendance/request-adjustment', [AttendanceController::class, 'requestAdjustment']);
    Route::post('attendance/{adjustment}/approve', [AttendanceController::class, 'approveAdjustment']);
    Route::post('attendance/{adjustment}/reject', [AttendanceController::class, 'rejectAdjustment']);
    Route::apiResource('attendance', AttendanceController::class)->only(['index', 'show']);
    Route::apiResource('shifts', ShiftController::class);

    // Leave Management
    Route::post('leave-requests/{leaveRequest}/approve', [LeaveRequestController::class, 'approve'])
        ->name('leave-requests.approve');
    Route::post('leave-requests/{leaveRequest}/reject', [LeaveRequestController::class, 'reject'])
        ->name('leave-requests.reject');
    Route::post('leave-requests/{leaveRequest}/cancel', [LeaveRequestController::class, 'cancel'])
        ->name('leave-requests.cancel');
    Route::get('leave-balance/{employee}', [LeaveRequestController::class, 'balance'])
        ->name('leave-requests.balance');
    // One-shot profile widget: balances + recent + upcoming + totals.
    Route::get('employees/{employee}/leave-summary', [LeaveRequestController::class, 'summary'])
        ->name('employees.leave-summary');
    Route::apiResource('leave-requests', LeaveRequestController::class)->only(['index', 'store', 'show']);
    Route::apiResource('leave-types', LeaveTypeController::class);

    // Leave balances — canonical plural form. The index endpoint takes
    // an employee_id query param; the update endpoint lets admins adjust
    // an allotment manually (company-scope leave.approve required).
    Route::get('leave-balances', [\App\Http\Controllers\Api\V1\LeaveBalanceController::class, 'index'])
        ->name('leave-balances.index');
    Route::put('leave-balances/{leaveBalance}', [\App\Http\Controllers\Api\V1\LeaveBalanceController::class, 'update'])
        ->name('leave-balances.update');

    // Frontend path aliases
    Route::get('leave/types', [LeaveTypeController::class, 'index']);
    Route::get('leave/requests', [LeaveRequestController::class, 'index']);
    Route::post('leave/requests', [LeaveRequestController::class, 'store']);
    Route::get('leave/requests/{leaveRequest}', [LeaveRequestController::class, 'show']);
    Route::post('leave/requests/{leaveRequest}/approve', [LeaveRequestController::class, 'approve']);
    Route::post('leave/requests/{leaveRequest}/reject', [LeaveRequestController::class, 'reject']);
    Route::post('leave/requests/{leaveRequest}/cancel', [LeaveRequestController::class, 'cancel']);
    Route::delete('leave/requests/{leaveRequest}', [LeaveRequestController::class, 'cancel']);

    // Current user's leave balances (frontend calls without an employee id)
    Route::get('leave/balances', function (Request $request) {
        $employee = \App\Models\Employee::where('user_id', $request->user()->id)->first();
        if (! $employee) {
            return response()->json(['data' => []]);
        }
        return app(LeaveRequestController::class)->balance($request, $employee->id);
    });
    Route::apiResource('holidays', HolidayController::class);

    // Documents
    Route::get('documents/{document}/download', [DocumentController::class, 'download'])
        ->name('documents.download');
    Route::apiResource('documents', DocumentController::class);

    // Task Board (Kanban)
    Route::post('boards/{board}/archive', [BoardController::class, 'archive'])->name('boards.archive');

    // Board members
    Route::get('boards/{board}/members', [\App\Http\Controllers\Api\V1\BoardMemberController::class, 'index']);
    Route::post('boards/{board}/members', [\App\Http\Controllers\Api\V1\BoardMemberController::class, 'store']);
    Route::delete('boards/{board}/members/{employee}', [\App\Http\Controllers\Api\V1\BoardMemberController::class, 'destroy']);

    // Board lists (columns)
    Route::get('boards/{board}/columns', [\App\Http\Controllers\Api\V1\BoardColumnController::class, 'index']);
    Route::post('boards/{board}/columns', [\App\Http\Controllers\Api\V1\BoardColumnController::class, 'store']);
    Route::put('board-columns/{column}', [\App\Http\Controllers\Api\V1\BoardColumnController::class, 'update']);
    Route::post('board-columns/{column}/archive', [\App\Http\Controllers\Api\V1\BoardColumnController::class, 'archive']);
    Route::post('board-columns/{column}/restore', [\App\Http\Controllers\Api\V1\BoardColumnController::class, 'restore']);
    Route::delete('board-columns/{column}', [\App\Http\Controllers\Api\V1\BoardColumnController::class, 'destroy']);

    Route::apiResource('boards', BoardController::class);
    Route::get('tasks/my-tasks', [TaskController::class, 'myTasks'])->name('tasks.my-tasks');
    Route::patch('tasks/{task}/move', [TaskController::class, 'move'])->name('tasks.move');
    Route::post('tasks/{task}/assign', [TaskController::class, 'assign'])->name('tasks.assign');
    Route::delete('tasks/{task}/assignees/{employee}', [TaskController::class, 'removeAssignee'])
        ->name('tasks.remove-assignee');
    Route::post('tasks/{task}/comments', [TaskController::class, 'addComment'])->name('tasks.add-comment');

    // Task attachments
    Route::get('tasks/{task}/attachments', [\App\Http\Controllers\Api\V1\TaskAttachmentController::class, 'index']);
    Route::post('tasks/{task}/attachments', [\App\Http\Controllers\Api\V1\TaskAttachmentController::class, 'store']);
    Route::get('tasks/{task}/attachments/{attachment}/download', [\App\Http\Controllers\Api\V1\TaskAttachmentController::class, 'download']);
    Route::delete('tasks/{task}/attachments/{attachment}', [\App\Http\Controllers\Api\V1\TaskAttachmentController::class, 'destroy']);

    Route::apiResource('tasks', TaskController::class);

    // Announcements
    Route::patch('announcements/{announcement}/publish', [AnnouncementController::class, 'publish']);
    Route::post('announcements/{announcement}/read', [AnnouncementController::class, 'markAsRead']);
    Route::post('announcements/{announcement}/acknowledge', [AnnouncementController::class, 'acknowledge']);
    Route::apiResource('announcements', AnnouncementController::class);

    // Performance Reviews
    Route::post('performance-reviews/{performanceReview}/submit', [PerformanceReviewController::class, 'submit']);
    Route::post('performance-reviews/{performanceReview}/acknowledge', [PerformanceReviewController::class, 'acknowledge']);
    Route::apiResource('performance-reviews', PerformanceReviewController::class)->only(['index', 'store', 'show']);
    Route::patch('review-cycles/{reviewCycle}/activate', [ReviewCycleController::class, 'activate']);
    Route::patch('review-cycles/{reviewCycle}/complete', [ReviewCycleController::class, 'complete']);
    Route::apiResource('review-cycles', ReviewCycleController::class);
    Route::patch('goals/{goal}/progress', [GoalController::class, 'updateProgress']);
    Route::apiResource('goals', GoalController::class);

    // Recruitment
    Route::get('job-openings/{jobOpening}/candidates', [JobOpeningController::class, 'candidates']);
    Route::apiResource('job-openings', JobOpeningController::class);
    Route::patch('candidates/{candidate}/move-stage', [CandidateController::class, 'moveStage']);
    Route::post('candidates/{candidate}/hire', [CandidateController::class, 'hire']);
    Route::post('candidates/{candidate}/reject', [CandidateController::class, 'reject']);
    Route::apiResource('candidates', CandidateController::class);
    Route::post('interviews/{interview}/feedback', [InterviewController::class, 'submitFeedback']);
    Route::apiResource('interviews', InterviewController::class);

    // Onboarding / Offboarding
    Route::apiResource('onboarding-templates', OnboardingTemplateController::class);
    Route::get('onboarding-checklists/employee/{employee}', [OnboardingChecklistController::class, 'getByEmployee']);
    Route::post('onboarding-checklists/{checklist}/complete-offboarding', [OnboardingChecklistController::class, 'completeOffboarding']);
    Route::post('onboarding-checklists/items/{item}/complete', [OnboardingChecklistController::class, 'completeItem']);
    Route::apiResource('onboarding-checklists', OnboardingChecklistController::class)->only(['index', 'store', 'show']);

    // Asset Management
    Route::get('assets/employee/{employee}', [AssetController::class, 'byEmployee']);
    Route::post('assets/{asset}/assign', [AssetController::class, 'assign']);
    Route::post('assets/{asset}/return', [AssetController::class, 'returnAsset']);
    Route::get('assets/{asset}/history', [AssetController::class, 'history']);
    Route::apiResource('assets', AssetController::class);

    // Payroll
    Route::get('salary-structures/{employee}', [SalaryStructureController::class, 'show']);
    Route::post('salary-structures', [SalaryStructureController::class, 'store']);
    Route::put('salary-structures/{employee}', [SalaryStructureController::class, 'update']);
    Route::post('payroll/generate', [PayrollController::class, 'generate']);
    Route::post('payroll/{payrollPeriod}/lock', [PayrollController::class, 'lock']);
    Route::get('payroll/{payrollPeriod}/export', [PayrollController::class, 'export']);
    Route::get('payroll/{payrollPeriod}', [PayrollController::class, 'show']);
    Route::get('payroll', [PayrollController::class, 'index']);

    // Reports
    Route::prefix('reports')->group(function () {
        Route::get('employees', [ReportController::class, 'employeeReport']);
        Route::get('attendance', [ReportController::class, 'attendanceReport']);
        Route::get('leave', [ReportController::class, 'leaveReport']);
        Route::get('tasks', [ReportController::class, 'taskReport']);
        Route::get('overdue-tasks', [ReportController::class, 'overdueTaskReport']);
        Route::get('document-expiry', [ReportController::class, 'documentExpiryReport']);
    });

    // Notifications
    Route::get('notifications', [NotificationController::class, 'index']);
    Route::get('notifications/unread-count', [NotificationController::class, 'unreadCount']);
    Route::post('notifications/mark-all-read', [NotificationController::class, 'markAllAsRead']);
    Route::post('notifications/{notification}/read', [NotificationController::class, 'markAsRead']);
    Route::delete('notifications/{notification}', [NotificationController::class, 'destroy']);
    // Send a targeted notification to one or more users / employees /
    // departments. Permission-gated inside the controller.
    Route::post('notifications', [NotificationController::class, 'store']);

    // Audit Logs
    Route::get('audit-logs', [AuditLogController::class, 'index']);
    Route::get('audit-logs/{auditLog}', [AuditLogController::class, 'show']);

    // Billing & Payments
    Route::get('my/subscription', [\App\Http\Controllers\Api\V1\BillingController::class, 'subscription']);
    Route::get('my/invoices', [\App\Http\Controllers\Api\V1\BillingController::class, 'invoices']);
    Route::get('billing/plans', [\App\Http\Controllers\Api\V1\BillingController::class, 'plans']);
    Route::post('payments/initiate', [\App\Http\Controllers\Api\V1\BillingController::class, 'initiatePayment']);
    Route::get('payments/verify/{id}', [\App\Http\Controllers\Api\V1\BillingController::class, 'verifyPayment']);
    Route::post('subscription/cancel', [\App\Http\Controllers\Api\V1\BillingController::class, 'cancelSubscription']);
});

// ── Public Routes (no auth) ──────────────────────────────────────────
Route::get('plans', \App\Http\Controllers\Api\V1\PublicPlanController::class);
Route::post('register', \App\Http\Controllers\Api\V1\RegistrationController::class);

// ── Super Admin Routes ───────────────────────────────────────────────
Route::prefix('super-admin')->middleware(['auth:sanctum', 'role:super_admin'])->group(function () {
    Route::get('dashboard', \App\Http\Controllers\Api\V1\SuperAdmin\DashboardController::class);

    Route::get('plans', [\App\Http\Controllers\Api\V1\SuperAdmin\PlanController::class, 'index']);
    Route::post('plans', [\App\Http\Controllers\Api\V1\SuperAdmin\PlanController::class, 'store']);
    Route::get('plans/{plan}', [\App\Http\Controllers\Api\V1\SuperAdmin\PlanController::class, 'show']);
    Route::put('plans/{plan}', [\App\Http\Controllers\Api\V1\SuperAdmin\PlanController::class, 'update']);
    Route::delete('plans/{plan}', [\App\Http\Controllers\Api\V1\SuperAdmin\PlanController::class, 'destroy']);

    Route::get('companies', [\App\Http\Controllers\Api\V1\SuperAdmin\CompanyManagementController::class, 'index']);
    Route::get('companies/{company}', [\App\Http\Controllers\Api\V1\SuperAdmin\CompanyManagementController::class, 'show']);
    Route::put('companies/{company}', [\App\Http\Controllers\Api\V1\SuperAdmin\CompanyManagementController::class, 'update']);
    Route::get('companies/{company}/stats', [\App\Http\Controllers\Api\V1\SuperAdmin\CompanyManagementController::class, 'stats']);
    Route::post('companies/{company}/change-plan', [\App\Http\Controllers\Api\V1\SuperAdmin\CompanyManagementController::class, 'changePlan']);

    Route::get('subscriptions', [\App\Http\Controllers\Api\V1\SuperAdmin\SubscriptionManagementController::class, 'index']);
    Route::put('subscriptions/{subscription}', [\App\Http\Controllers\Api\V1\SuperAdmin\SubscriptionManagementController::class, 'update']);

    Route::get('payments', [\App\Http\Controllers\Api\V1\SuperAdmin\PaymentManagementController::class, 'index']);
    Route::get('payments/{payment}', [\App\Http\Controllers\Api\V1\SuperAdmin\PaymentManagementController::class, 'show']);
});

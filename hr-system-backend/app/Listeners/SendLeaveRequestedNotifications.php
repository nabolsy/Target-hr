<?php

namespace App\Listeners;

use App\Events\LeaveRequested;
use App\Events\NotificationSent;
use App\Models\Department;
use App\Models\NotificationLog;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Notify approvers when an employee files a leave request.
 *
 * Approvers are resolved as everyone in the requester's company who
 * holds either of the Spatie permissions used to gate leave actions:
 *
 *   - `leave.approve`  → singular form consumed by PermissionService /
 *                        LeaveRequestPolicy. Granted to HR Manager,
 *                        Department Manager, Team Lead, Company Admin.
 *   - `leave.view`     → wider; we include it as a fallback so admins
 *                        who have read-but-not-approve still get the
 *                        heads-up that something is in the queue.
 *
 * Each approver gets a NotificationLog row AND a real-time push via
 * NotificationSent (same Echo channel the manual-send endpoint uses),
 * so the bell badge bumps and the toast pops immediately for any
 * admin/manager logged in. Broadcast failures are non-fatal — the row
 * persists either way, the user sees it on next poll.
 *
 * Filed pending → approver should act. We DO NOT notify the requester
 * here; the existing audit trail and the requester's own visibility
 * into their leave list cover that.
 */
class SendLeaveRequestedNotifications
{
    public function handle(LeaveRequested $event): void
    {
        $leave = $event->leaveRequest->loadMissing(['employee', 'leaveType']);
        $companyId = $leave->company_id;
        $employee = $leave->employee;

        // Pull approvers via Spatie's role→permission relation. We
        // intentionally check BOTH permission shapes (plural "leaves.*"
        // from SystemPermissionSeeder used to exist; current source is
        // singular "leave.approve" from role_access.php). whereHas with
        // an `or` on permissions table covers either.
        $approvers = User::query()
            ->where('company_id', $companyId)
            ->where(function ($q) use ($employee) {
                $q->whereHas('roles.permissions', function ($qq) {
                    $qq->whereIn('name', ['leave.approve', 'leave.view']);
                })->orWhereHas('permissions', function ($qq) {
                    $qq->whereIn('name', ['leave.approve', 'leave.view']);
                });
                if ($employee?->user_id) {
                    // Don't double-notify the requester even if they
                    // happen to also hold leave.view (e.g. a manager
                    // requesting their own leave).
                    $q->where('id', '!=', $employee->user_id);
                }
            })
            ->get();

        if ($approvers->isEmpty()) {
            return;
        }

        $employeeName = trim(($employee?->first_name ?? '').' '.($employee?->last_name ?? ''))
            ?: $employee?->email
            ?: 'An employee';
        $title = "{$employeeName} requested leave";
        $body = sprintf(
            '%s · %s → %s · %s day(s)',
            $leave->leaveType?->name ?? 'Leave',
            $leave->start_date?->format('M j, Y') ?? $leave->start_date,
            $leave->end_date?->format('M j, Y') ?? $leave->end_date,
            (string) $leave->total_days,
        );

        foreach ($approvers as $approver) {
            $log = NotificationLog::create([
                'company_id' => $companyId,
                'user_id' => $approver->id,
                'type' => 'leave',
                'title' => $title,
                'body' => $body,
                'data' => [
                    'type' => 'leave',
                    'title' => $title,
                    'message' => $body,
                    'leave_request_id' => $leave->id,
                    'employee_id' => $employee?->id,
                    'employee_name' => $employeeName,
                    'leave_type' => $leave->leaveType?->name,
                    'start_date' => $leave->start_date?->toDateString(),
                    'end_date' => $leave->end_date?->toDateString(),
                    'total_days' => (string) $leave->total_days,
                    'url' => '/leave-requests?status=pending',
                ],
            ]);

            try {
                broadcast(new NotificationSent($log));
            } catch (Throwable $e) {
                Log::warning('LeaveRequested broadcast failed', [
                    'user_id' => $approver->id,
                    'leave_request_id' => $leave->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}

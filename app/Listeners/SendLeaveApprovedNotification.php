<?php

namespace App\Listeners;

use App\Events\LeaveApproved;
use App\Events\NotificationSent;
use App\Models\NotificationLog;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Notify the requester when their leave request is approved.
 *
 * Mirrors SendLeaveRejectedNotification — kept as a separate class
 * because Laravel's listener auto-discovery requires exactly one
 * type-hinted `handle()` per listener. The shared row-creation
 * payload is duplicated rather than abstracted; the rejection variant
 * also needs the rejection reason, so a shared helper would only save
 * a few lines at the cost of indirection.
 *
 * Skips silently when the employee has no linked user account.
 */
class SendLeaveApprovedNotification
{
    public function handle(LeaveApproved $event): void
    {
        $leave = $event->leaveRequest->loadMissing(['employee', 'leaveType', 'approver']);
        $userId = $leave->employee?->user_id;
        if (! $userId) {
            return;
        }

        $title = 'Your leave request was approved';
        $body = sprintf(
            '%s · %s → %s · %s day(s)',
            $leave->leaveType?->name ?? 'Leave',
            $leave->start_date?->format('M j, Y') ?? $leave->start_date,
            $leave->end_date?->format('M j, Y') ?? $leave->end_date,
            (string) $leave->total_days,
        );

        $log = NotificationLog::create([
            'company_id' => $leave->company_id,
            'user_id' => $userId,
            'type' => 'leave',
            'title' => $title,
            'body' => $body,
            'data' => [
                'type' => 'leave',
                'title' => $title,
                'message' => $body,
                'decision' => 'approved',
                'leave_request_id' => $leave->id,
                'leave_type' => $leave->leaveType?->name,
                'start_date' => $leave->start_date?->toDateString(),
                'end_date' => $leave->end_date?->toDateString(),
                'total_days' => (string) $leave->total_days,
                'approver_name' => $leave->approver?->name,
                'url' => '/leave-requests',
            ],
        ]);

        try {
            broadcast(new NotificationSent($log));
        } catch (Throwable $e) {
            Log::warning('LeaveApproved broadcast failed', [
                'user_id' => $userId,
                'leave_request_id' => $leave->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}

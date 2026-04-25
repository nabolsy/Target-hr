<?php

namespace App\Listeners;

use App\Events\LeaveRejected;
use App\Events\NotificationSent;
use App\Models\NotificationLog;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Notify the requester when their leave request is rejected.
 *
 * Sibling to SendLeaveApprovedNotification — same shape, different
 * event + a body that includes the rejection_reason when one was
 * supplied so the requester sees WHY without having to open the page.
 *
 * Skips silently when the employee has no linked user account.
 */
class SendLeaveRejectedNotification
{
    public function handle(LeaveRejected $event): void
    {
        $leave = $event->leaveRequest->loadMissing(['employee', 'leaveType', 'approver']);
        $userId = $leave->employee?->user_id;
        if (! $userId) {
            return;
        }

        $title = 'Your leave request was rejected';
        $body = sprintf(
            '%s · %s → %s · %s day(s)%s',
            $leave->leaveType?->name ?? 'Leave',
            $leave->start_date?->format('M j, Y') ?? $leave->start_date,
            $leave->end_date?->format('M j, Y') ?? $leave->end_date,
            (string) $leave->total_days,
            $leave->rejection_reason ? ' · '.$leave->rejection_reason : '',
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
                'decision' => 'rejected',
                'leave_request_id' => $leave->id,
                'leave_type' => $leave->leaveType?->name,
                'start_date' => $leave->start_date?->toDateString(),
                'end_date' => $leave->end_date?->toDateString(),
                'total_days' => (string) $leave->total_days,
                'approver_name' => $leave->approver?->name,
                'rejection_reason' => $leave->rejection_reason,
                'url' => '/leave-requests',
            ],
        ]);

        try {
            broadcast(new NotificationSent($log));
        } catch (Throwable $e) {
            Log::warning('LeaveRejected broadcast failed', [
                'user_id' => $userId,
                'leave_request_id' => $leave->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}

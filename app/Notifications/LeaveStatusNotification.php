<?php

namespace App\Notifications;

use App\Models\LeaveRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LeaveStatusNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private LeaveRequest $leaveRequest,
        private string $status
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $statusLabel = ucfirst($this->status);

        return (new MailMessage)
            ->subject("Leave Request {$statusLabel}")
            ->greeting("Hello {$notifiable->name},")
            ->line("Your leave request has been **{$this->status}**.")
            ->line("Leave Type: {$this->leaveRequest->leaveType?->name}")
            ->line("From: {$this->leaveRequest->start_date->toDateString()}")
            ->line("To: {$this->leaveRequest->end_date->toDateString()}")
            ->line("Total Days: {$this->leaveRequest->total_days}")
            ->when($this->status === 'rejected' && $this->leaveRequest->rejection_reason, function (MailMessage $mail) {
                return $mail->line("Reason: {$this->leaveRequest->rejection_reason}");
            })
            ->action('View Leave Request', url("/leave-requests/{$this->leaveRequest->id}"));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'leave_status',
            'leave_request_id' => $this->leaveRequest->id,
            'status' => $this->status,
            'message' => "Your leave request from {$this->leaveRequest->start_date->toDateString()} to {$this->leaveRequest->end_date->toDateString()} has been {$this->status}.",
        ];
    }
}

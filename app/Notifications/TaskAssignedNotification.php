<?php

namespace App\Notifications;

use App\Models\Employee;
use App\Models\Task;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TaskAssignedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private Task $task,
        private Employee $employee
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Task Assigned: {$this->task->title}")
            ->greeting("Hello {$this->employee->full_name},")
            ->line("You have been assigned a new task: **{$this->task->title}**.")
            ->line("Priority: {$this->task->priority?->value}")
            ->line("Due Date: " . ($this->task->due_date?->toDateString() ?? 'No due date'))
            ->when($this->task->description, function (MailMessage $mail) {
                return $mail->line("Description: {$this->task->description}");
            })
            ->action('View Task', url("/tasks/{$this->task->id}"))
            ->line('Please review and begin working on this task.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'task_assigned',
            'task_id' => $this->task->id,
            'task_title' => $this->task->title,
            'message' => "You have been assigned the task: {$this->task->title}",
        ];
    }
}

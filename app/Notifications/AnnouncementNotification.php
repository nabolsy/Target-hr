<?php

namespace App\Notifications;

use App\Models\Announcement;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AnnouncementNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private Announcement $announcement
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("New Announcement: {$this->announcement->title}")
            ->greeting("Hello {$notifiable->name},")
            ->line("A new announcement has been posted: **{$this->announcement->title}**.")
            ->line($this->announcement->body)
            ->when($this->announcement->requires_acknowledgement, function (MailMessage $mail) {
                return $mail->line('This announcement requires your acknowledgement.');
            })
            ->action('View Announcement', url("/announcements/{$this->announcement->id}"));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'announcement',
            'announcement_id' => $this->announcement->id,
            'announcement_title' => $this->announcement->title,
            'message' => "New announcement: {$this->announcement->title}",
        ];
    }
}

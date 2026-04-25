<?php

namespace App\Notifications;

use App\Models\EmployeeDocument;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DocumentExpiringNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private EmployeeDocument $document
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $daysUntilExpiry = now()->diffInDays($this->document->expiry_date, false);

        return (new MailMessage)
            ->subject("Document Expiring: {$this->document->title}")
            ->greeting("Hello {$notifiable->name},")
            ->line("Your document **{$this->document->title}** is expiring soon.")
            ->line("Expiry Date: {$this->document->expiry_date->toDateString()}")
            ->line("Days Remaining: {$daysUntilExpiry}")
            ->action('View Document', url("/documents/{$this->document->id}"))
            ->line('Please take action to renew or update this document before it expires.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'document_expiring',
            'document_id' => $this->document->id,
            'document_title' => $this->document->title,
            'expiry_date' => $this->document->expiry_date->toDateString(),
            'message' => "Document \"{$this->document->title}\" is expiring on {$this->document->expiry_date->toDateString()}.",
        ];
    }
}

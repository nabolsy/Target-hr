<?php

namespace App\Events;

use App\Models\NotificationLog;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Fires after a NotificationLog row is written by NotificationService::send().
 * Streams the same payload the REST API would return so the frontend can
 * prepend the new entry to its list and bump the unread badge without a
 * round-trip to /notifications.
 *
 * Channel: private-user.{user_id} — matches `Echo.private('user.' + id)`
 * Event name: `notification.sent` (broadcastAs).
 */
class NotificationSent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public NotificationLog $notification)
    {
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('user.'.$this->notification->user_id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'notification.sent';
    }

    public function broadcastWith(): array
    {
        $n = $this->notification;

        return [
            'id' => $n->id,
            'type' => $n->type,
            'title' => $n->title,
            'body' => $n->body,
            'data' => $n->data,
            'read_at' => $n->read_at?->toISOString(),
            'created_at' => $n->created_at?->toISOString(),
        ];
    }
}

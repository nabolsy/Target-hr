<?php

namespace App\Services;

use App\Models\NotificationLog;
use App\Models\User;
use Illuminate\Notifications\Notification;
use Illuminate\Pagination\LengthAwarePaginator;

class NotificationService
{
    public function send(User $user, Notification $notification): void
    {
        $user->notify($notification);

        $data = method_exists($notification, 'toArray') ? $notification->toArray($user) : [];

        NotificationLog::create([
            'company_id' => $user->company_id,
            'user_id' => $user->id,
            'type' => $data['type'] ?? class_basename($notification),
            'title' => $data['message'] ?? $data['type'] ?? class_basename($notification),
            'body' => $data['message'] ?? null,
            'data' => $data,
        ]);
    }

    public function getForUser(int $userId, int $perPage = 15): LengthAwarePaginator
    {
        return NotificationLog::forUser($userId)
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    public function markAsRead(int $notificationId): NotificationLog
    {
        $notification = NotificationLog::findOrFail($notificationId);
        $notification->update(['read_at' => now()]);

        return $notification;
    }

    public function markAllAsRead(int $userId): int
    {
        return NotificationLog::forUser($userId)
            ->unread()
            ->update(['read_at' => now()]);
    }

    public function getUnreadCount(int $userId): int
    {
        return NotificationLog::forUser($userId)
            ->unread()
            ->count();
    }

    public function deleteOld(int $days = 90): int
    {
        return NotificationLog::where('created_at', '<', now()->subDays($days))
            ->delete();
    }
}

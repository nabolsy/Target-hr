<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\NotificationResource;
use App\Models\NotificationLog;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\Response;

class NotificationController extends Controller
{
    public function __construct(private NotificationService $notificationService)
    {
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $notifications = $this->notificationService->getForUser(
            auth()->id(),
            $request->integer('per_page', 15)
        );

        return NotificationResource::collection($notifications);
    }

    public function markAsRead(NotificationLog $notification): JsonResponse
    {
        $this->authorize('update', $notification);

        $this->notificationService->markAsRead($notification->id);

        return response()->json(['message' => 'Notification marked as read.']);
    }

    public function markAllAsRead(): JsonResponse
    {
        $count = $this->notificationService->markAllAsRead(auth()->id());

        return response()->json([
            'message' => 'All notifications marked as read.',
            'count' => $count,
        ]);
    }

    public function unreadCount(): JsonResponse
    {
        $count = $this->notificationService->getUnreadCount(auth()->id());

        return response()->json(['unread_count' => $count]);
    }

    public function destroy(NotificationLog $notification): JsonResponse
    {
        $this->authorize('delete', $notification);

        $notification->delete();

        return response()->json(['message' => 'Notification deleted successfully.'], Response::HTTP_OK);
    }
}

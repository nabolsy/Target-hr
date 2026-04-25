<?php

namespace App\Http\Controllers\Api\V1;

use App\Events\NotificationSent;
use App\Http\Controllers\Controller;
use App\Http\Resources\NotificationResource;
use App\Models\Employee;
use App\Models\NotificationLog;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

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

    /**
     * Send a manual notification to one or more recipients.
     *
     * Recipients can be specified as user_ids, employee_ids (resolved
     * to their linked users), or department_ids (broadcast to every
     * user in the department's employees). Permission-gated by
     * `notifications.send` (plural — matches SystemPermissionSeeder
     * and the role-picker UI). Each recipient gets a NotificationLog
     * row AND a real-time Echo push via NotificationSent.
     */
    public function store(Request $request): JsonResponse
    {
        if (! auth()->user()?->can('notifications.send')) {
            return response()->json([
                'message' => 'You do not have permission to send notifications.',
            ], Response::HTTP_FORBIDDEN);
        }

        $data = $request->validate([
            'title' => 'required|string|max:255',
            'body' => 'nullable|string|max:2000',
            'type' => 'nullable|string|max:50',
            'user_ids' => 'sometimes|array',
            'user_ids.*' => 'integer|exists:users,id',
            'employee_ids' => 'sometimes|array',
            'employee_ids.*' => 'integer|exists:employees,id',
            'department_ids' => 'sometimes|array',
            'department_ids.*' => 'integer|exists:departments,id',
        ]);

        $companyId = auth()->user()->company_id;
        $userIds = collect($data['user_ids'] ?? []);

        // Resolve employee_ids → user_ids (only employees that have a
        // linked user account can receive notifications).
        if (! empty($data['employee_ids'])) {
            $fromEmployees = Employee::whereIn('id', $data['employee_ids'])
                ->where('company_id', $companyId)
                ->whereNotNull('user_id')
                ->pluck('user_id');
            $userIds = $userIds->concat($fromEmployees);
        }

        // Resolve department_ids → all employees in those departments → user_ids.
        if (! empty($data['department_ids'])) {
            $fromDepartments = Employee::whereIn('department_id', $data['department_ids'])
                ->where('company_id', $companyId)
                ->whereNotNull('user_id')
                ->pluck('user_id');
            $userIds = $userIds->concat($fromDepartments);
        }

        // Tenant guard: drop any user that doesn't live in the same
        // company as the sender (defense-in-depth even though the
        // joins above already filter by company_id).
        $userIds = User::whereIn('id', $userIds->unique())
            ->where('company_id', $companyId)
            ->pluck('id');

        if ($userIds->isEmpty()) {
            return response()->json([
                'message' => 'No valid recipients provided.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $type = $data['type'] ?? 'general';
        $title = $data['title'];
        $body = $data['body'] ?? null;

        $created = [];
        $broadcastFailures = 0;
        foreach ($userIds as $uid) {
            $log = NotificationLog::create([
                'company_id' => $companyId,
                'user_id' => $uid,
                'type' => $type,
                'title' => $title,
                'body' => $body,
                'data' => [
                    'type' => $type,
                    'title' => $title,
                    'message' => $body,
                    'sender_id' => auth()->id(),
                    'sender_name' => auth()->user()->name,
                ],
            ]);
            // Real-time push is best-effort. The DB row is the source
            // of truth — if Reverb is down, recipients will still see
            // the notification on their next /notifications poll
            // (60s) or page reload. Surfacing the cURL error to the
            // sender as a 500 would block a working code path.
            try {
                broadcast(new NotificationSent($log));
            } catch (Throwable $e) {
                $broadcastFailures++;
                Log::warning('NotificationSent broadcast failed', [
                    'user_id' => $uid,
                    'notification_id' => $log->id,
                    'error' => $e->getMessage(),
                ]);
            }
            $created[] = $log;
        }

        $payload = [
            'message' => 'Notification sent to '.count($created).' recipient(s).',
            'recipients_count' => count($created),
        ];
        if ($broadcastFailures > 0) {
            $payload['warning'] = 'Real-time push failed for '.$broadcastFailures
                .' recipient(s); they will see the notification on their next refresh.';
        }

        return response()->json($payload, Response::HTTP_CREATED);
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

<?php

namespace App\Http\Controllers\Api\V1;

use App\Events\NotificationSent;
use App\Http\Controllers\Controller;
use App\Models\Board;
use App\Models\Employee;
use App\Models\NotificationLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class BoardMemberController extends Controller
{
    public function index(Board $board): JsonResponse
    {
        // Anyone who can view the board can see its members.
        $this->authorize('view', $board);

        $board->load('members');

        return response()->json([
            'data' => $board->members->map(fn (Employee $e) => $this->format($e)),
        ]);
    }

    public function store(Request $request, Board $board): JsonResponse
    {
        $this->authorize('manageMembers', $board);

        $data = $request->validate([
            'employee_id' => 'required_without:employee_ids|integer|exists:employees,id',
            'employee_ids' => 'required_without:employee_id|array',
            'employee_ids.*' => 'integer|exists:employees,id',
        ]);

        $ids = ! empty($data['employee_ids'])
            ? $data['employee_ids']
            : [$data['employee_id']];

        // Capture pre-existing member ids so we only notify the diff —
        // re-adding someone who's already on the board shouldn't fire
        // a duplicate "you were added to a board" notification.
        $alreadyMembers = $board->members()->pluck('employees.id')->all();
        $newlyAdded = array_values(array_diff($ids, $alreadyMembers));

        $payload = [];
        foreach ($ids as $id) {
            $payload[$id] = [
                'added_at' => now(),
                'added_by' => $request->user()->id,
            ];
        }

        $board->members()->syncWithoutDetaching($payload);
        $board->load('members');

        // Best-effort notify each newly added member (those with a
        // linked user account). Broadcast failures are logged, never
        // surfaced — the row persists and shows up on the next poll.
        if (! empty($newlyAdded)) {
            $this->notifyNewMembers($board, $newlyAdded);
        }

        return response()->json([
            'data' => $board->members->map(fn (Employee $e) => $this->format($e)),
            'message' => 'Members added successfully.',
        ], 201);
    }

    private function notifyNewMembers(Board $board, array $employeeIds): void
    {
        $employees = Employee::whereIn('id', $employeeIds)
            ->whereNotNull('user_id')
            ->get();
        if ($employees->isEmpty()) {
            return;
        }

        $boardName = $board->name ?: 'a board';
        $title = "You were added to {$boardName}";
        $body = "You can now view and collaborate on tasks in this board.";

        foreach ($employees as $emp) {
            $log = NotificationLog::create([
                'company_id' => $board->company_id,
                'user_id' => $emp->user_id,
                'type' => 'task',
                'title' => $title,
                'body' => $body,
                'data' => [
                    'type' => 'task',
                    'title' => $title,
                    'message' => $body,
                    'board_id' => $board->id,
                    'board_name' => $boardName,
                    'url' => "/boards/{$board->id}",
                ],
            ]);
            try {
                broadcast(new NotificationSent($log));
            } catch (Throwable $e) {
                Log::warning('BoardMembersAdded broadcast failed', [
                    'user_id' => $emp->user_id,
                    'board_id' => $board->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    public function destroy(Board $board, Employee $employee): JsonResponse
    {
        $this->authorize('manageMembers', $board);

        $board->members()->detach($employee->id);

        return response()->json(['message' => 'Member removed.']);
    }

    private function format(Employee $e): array
    {
        return [
            'id' => $e->id,
            'first_name' => $e->first_name,
            'last_name' => $e->last_name,
            'full_name' => trim("{$e->first_name} {$e->last_name}"),
            'email' => $e->email,
            'profile_image' => $e->profile_image,
        ];
    }
}

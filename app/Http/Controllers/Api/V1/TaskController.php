<?php

namespace App\Http\Controllers\Api\V1;

use App\DTOs\TaskDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\MoveTaskRequest;
use App\Http\Requests\StoreTaskCommentRequest;
use App\Http\Requests\StoreTaskRequest;
use App\Http\Requests\UpdateTaskRequest;
use App\Http\Resources\TaskCommentResource;
use App\Http\Resources\TaskResource;
use App\Models\Task;
use App\Models\TaskComment;
use App\Services\Access\PermissionService;
use App\Services\BoardService;
use App\Services\TaskService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\Response;

class TaskController extends Controller
{
    public function __construct(
        private TaskService $taskService,
        private BoardService $boardService,
        private PermissionService $permissions,
    ) {
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Task::class);

        $user = $request->user();

        $filters = $request->only([
            'board_id', 'column_id', 'company_id', 'priority',
            'assignee_id', 'search', 'is_archived', 'sort_by', 'sort_dir',
        ]);

        if ($user && $user->company_id) {
            $filters['company_id'] = $user->company_id;
        }

        // Restrict to tasks on boards the user can see. This reuses the
        // exact same visibility rules BoardController::index uses, so a
        // task list and a boards list cannot diverge.
        $visibleBoards = $this->boardService->getVisibleForUser($user);
        $filters['__visible_board_ids'] = $visibleBoards->pluck('id')->all();

        $tasks = $this->taskService->paginateWithFilters(
            $filters,
            $request->integer('per_page', 15)
        );

        return TaskResource::collection($tasks);
    }

    public function store(StoreTaskRequest $request): JsonResponse
    {
        $this->authorize('create', Task::class);

        $dto = TaskDTO::fromArray($request->validated());
        $task = $this->taskService->createTask($dto);

        return (new TaskResource($task))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(Task $task): TaskResource
    {
        $this->authorize('view', $task);

        $task->load([
            'assignees', 'labels', 'column', 'creator', 'board',
            'comments' => function ($q) {
                $q->whereNull('parent_id')->orderBy('created_at');
            },
            'comments.user', 'comments.replies.user',
            'attachments', 'checklists.items',
        ]);

        return new TaskResource($task);
    }

    public function update(UpdateTaskRequest $request, Task $task): TaskResource
    {
        $this->authorize('update', $task);

        $dto = TaskDTO::fromArray($request->validated());
        $result = $this->taskService->updateTask($task->id, $dto);

        return new TaskResource($result);
    }

    public function destroy(Task $task): JsonResponse
    {
        $this->authorize('delete', $task);

        $this->taskService->deleteTask($task->id);

        return response()->json(['message' => 'Task deleted successfully.'], Response::HTTP_OK);
    }

    public function move(MoveTaskRequest $request, Task $task): TaskResource
    {
        // `move` has a self-fallback inside the policy so assignees keep
        // drag-drop access to their own cards — no UX change.
        $this->authorize('move', $task);

        $result = $this->taskService->moveTask(
            $task->id,
            $request->validated('column_id'),
            $request->validated('sort_order')
        );

        return new TaskResource($result);
    }

    public function assign(Request $request, Task $task): TaskResource
    {
        $this->authorize('assign', $task);

        $request->validate(['employee_id' => 'required|integer|exists:employees,id']);

        $result = $this->taskService->assignTask($task->id, $request->input('employee_id'));

        return new TaskResource($result);
    }

    public function removeAssignee(Task $task, int $employeeId): TaskResource
    {
        $this->authorize('assign', $task);

        $result = $this->taskService->removeAssignee($task->id, $employeeId);

        return new TaskResource($result);
    }

    public function listComments(Task $task): AnonymousResourceCollection
    {
        $this->authorize('view', $task);

        $comments = $this->taskService->listComments($task->id);

        return TaskCommentResource::collection($comments);
    }

    public function addComment(StoreTaskCommentRequest $request, Task $task): JsonResponse
    {
        $this->authorize('comment', $task);

        $comment = $this->taskService->addComment(
            $task->id,
            $request->validated('body'),
            $request->input('parent_id') ? (int) $request->input('parent_id') : null,
        );

        return (new TaskCommentResource($comment))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function deleteComment(Task $task, TaskComment $comment): JsonResponse
    {
        if ((int) $comment->task_id !== (int) $task->id) {
            return response()->json(['message' => 'Comment does not belong to this task.'], Response::HTTP_NOT_FOUND);
        }

        $this->authorize('deleteComment', [$task, $comment]);

        $this->taskService->deleteComment($comment->id);

        return response()->json(['message' => 'Comment deleted.'], Response::HTTP_OK);
    }

    public function myTasks(Request $request): AnonymousResourceCollection
    {
        // My tasks is always self-scoped by definition — no permission
        // check beyond auth:sanctum is needed because the service method
        // filters by the current user's id.
        $tasks = $this->taskService->getMyTasks($request->user()->id);

        return TaskResource::collection($tasks);
    }
}

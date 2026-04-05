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
use App\Services\TaskService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\Response;

class TaskController extends Controller
{
    public function __construct(private TaskService $taskService)
    {
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $tasks = $this->taskService->paginateWithFilters(
            $request->only([
                'board_id', 'column_id', 'company_id', 'priority',
                'assignee_id', 'search', 'is_archived', 'sort_by', 'sort_dir',
            ]),
            $request->integer('per_page', 15)
        );

        return TaskResource::collection($tasks);
    }

    public function store(StoreTaskRequest $request): JsonResponse
    {
        $dto = TaskDTO::fromArray($request->validated());
        $task = $this->taskService->createTask($dto);

        return (new TaskResource($task))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(Task $task): TaskResource
    {
        $task->load([
            'assignees', 'labels', 'column', 'creator', 'board',
            'comments.user', 'attachments', 'checklists.items',
        ]);

        return new TaskResource($task);
    }

    public function update(UpdateTaskRequest $request, Task $task): TaskResource
    {
        $dto = TaskDTO::fromArray($request->validated());
        $result = $this->taskService->updateTask($task->id, $dto);

        return new TaskResource($result);
    }

    public function destroy(Task $task): JsonResponse
    {
        $this->taskService->deleteTask($task->id);

        return response()->json(['message' => 'Task deleted successfully.'], Response::HTTP_OK);
    }

    public function move(MoveTaskRequest $request, Task $task): TaskResource
    {
        $result = $this->taskService->moveTask(
            $task->id,
            $request->validated('column_id'),
            $request->validated('sort_order')
        );

        return new TaskResource($result);
    }

    public function assign(Request $request, Task $task): TaskResource
    {
        $request->validate(['employee_id' => 'required|integer|exists:employees,id']);

        $result = $this->taskService->assignTask($task->id, $request->input('employee_id'));

        return new TaskResource($result);
    }

    public function removeAssignee(Task $task, int $employeeId): TaskResource
    {
        $result = $this->taskService->removeAssignee($task->id, $employeeId);

        return new TaskResource($result);
    }

    public function addComment(StoreTaskCommentRequest $request, Task $task): JsonResponse
    {
        $comment = $this->taskService->addComment($task->id, $request->validated('body'));

        return (new TaskCommentResource($comment))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function myTasks(Request $request): AnonymousResourceCollection
    {
        $tasks = $this->taskService->getMyTasks($request->user()->id);

        return TaskResource::collection($tasks);
    }
}

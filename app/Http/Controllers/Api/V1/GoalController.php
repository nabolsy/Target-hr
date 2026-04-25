<?php

namespace App\Http\Controllers\Api\V1;

use App\DTOs\GoalDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreGoalRequest;
use App\Http\Requests\UpdateGoalRequest;
use App\Http\Resources\GoalResource;
use App\Models\Goal;
use App\Services\GoalService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\Response;

class GoalController extends Controller
{
    public function __construct(private GoalService $goalService)
    {
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        if ($request->has('employee_id')) {
            $goals = $this->goalService->getByEmployee($request->integer('employee_id'));
        } elseif ($request->has('review_cycle_id')) {
            $goals = $this->goalService->getByCycle($request->integer('review_cycle_id'));
        } else {
            $goals = $this->goalService->paginate($request->integer('per_page', 15));
        }

        return GoalResource::collection($goals);
    }

    public function store(StoreGoalRequest $request): JsonResponse
    {
        $dto = GoalDTO::fromArray($request->validated());
        $goal = $this->goalService->createGoal($dto);

        return (new GoalResource($goal->load(['employee', 'reviewCycle'])))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(Goal $goal): GoalResource
    {
        $goal->load(['employee', 'reviewCycle']);

        return new GoalResource($goal);
    }

    public function update(UpdateGoalRequest $request, Goal $goal): GoalResource
    {
        $dto = GoalDTO::fromArray($request->validated());
        $result = $this->goalService->updateGoal($goal->id, $dto);

        return new GoalResource($result->load(['employee', 'reviewCycle']));
    }

    public function destroy(Goal $goal): JsonResponse
    {
        $this->goalService->delete($goal->id);

        return response()->json(['message' => 'Goal deleted successfully.'], Response::HTTP_OK);
    }

    public function updateProgress(Request $request, Goal $goal): GoalResource
    {
        $request->validate([
            'current_value' => ['required', 'numeric', 'min:0'],
            'status' => ['nullable', 'string'],
        ]);

        $result = $this->goalService->updateProgress(
            $goal->id,
            $request->input('current_value'),
            $request->input('status')
        );

        return new GoalResource($result->load(['employee', 'reviewCycle']));
    }
}

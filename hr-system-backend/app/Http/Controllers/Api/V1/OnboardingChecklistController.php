<?php

namespace App\Http\Controllers\Api\V1;

use App\DTOs\OnboardingChecklistDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\CompleteChecklistItemRequest;
use App\Http\Requests\StoreOnboardingChecklistRequest;
use App\Http\Resources\OnboardingChecklistItemResource;
use App\Http\Resources\OnboardingChecklistResource;
use App\Models\OnboardingChecklist;
use App\Models\OnboardingChecklistItem;
use App\Services\OnboardingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\Response;

class OnboardingChecklistController extends Controller
{
    public function __construct(private OnboardingService $onboardingService)
    {
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $filters = $request->only([
            'company_id', 'employee_id', 'type', 'status',
            'created_by', 'sort_by', 'sort_dir',
        ]);

        if (empty($filters['company_id'])) {
            $filters['company_id'] = auth()->user()->company_id;
        }

        $checklists = $this->onboardingService->paginateWithFilters(
            $filters,
            $request->integer('per_page', 15)
        );

        return OnboardingChecklistResource::collection($checklists);
    }

    public function store(StoreOnboardingChecklistRequest $request): JsonResponse
    {
        $validated = $request->validated();

        if (! empty($validated['template_id'])) {
            $checklist = $this->onboardingService->createFromTemplate(
                $validated['employee_id'],
                $validated['template_id']
            );
        } else {
            $dto = OnboardingChecklistDTO::fromArray($validated);
            $checklist = $this->onboardingService->createChecklist($dto);
        }

        return (new OnboardingChecklistResource($checklist->load(['items', 'employee'])))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(OnboardingChecklist $onboardingChecklist): OnboardingChecklistResource
    {
        $onboardingChecklist->load(['items', 'employee', 'template', 'creator']);

        return new OnboardingChecklistResource($onboardingChecklist);
    }

    public function completeItem(CompleteChecklistItemRequest $request, OnboardingChecklistItem $checklistItem): OnboardingChecklistItemResource
    {
        $item = $this->onboardingService->completeItem(
            $checklistItem->id,
            $request->validated('notes')
        );

        return new OnboardingChecklistItemResource($item->load(['assignedTo', 'completedBy']));
    }

    public function getByEmployee(Request $request, int $employeeId): AnonymousResourceCollection
    {
        $checklists = $this->onboardingService->getByEmployee($employeeId);

        return OnboardingChecklistResource::collection($checklists);
    }

    public function completeOffboarding(OnboardingChecklist $onboardingChecklist): OnboardingChecklistResource
    {
        $checklist = $this->onboardingService->completeOffboarding($onboardingChecklist->id);

        return new OnboardingChecklistResource($checklist->load(['items', 'employee']));
    }
}

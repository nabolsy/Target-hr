<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreReviewCycleRequest;
use App\Http\Resources\ReviewCycleResource;
use App\Models\ReviewCycle;
use App\Services\ReviewCycleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\Response;

class ReviewCycleController extends Controller
{
    public function __construct(private ReviewCycleService $cycleService)
    {
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $companyId = $request->input('company_id', auth()->user()->company_id);
        $cycles = $this->cycleService->getByCompany($companyId);

        return ReviewCycleResource::collection($cycles);
    }

    public function store(StoreReviewCycleRequest $request): JsonResponse
    {
        $cycle = $this->cycleService->createCycle($request->validated());

        return (new ReviewCycleResource($cycle->load('creator')))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(ReviewCycle $reviewCycle): ReviewCycleResource
    {
        $reviewCycle->load(['creator', 'reviews.employee', 'reviews.reviewer', 'goals']);

        return new ReviewCycleResource($reviewCycle);
    }

    public function update(StoreReviewCycleRequest $request, ReviewCycle $reviewCycle): ReviewCycleResource
    {
        $cycle = $this->cycleService->updateCycle($reviewCycle->id, $request->validated());

        return new ReviewCycleResource($cycle->load('creator'));
    }

    public function destroy(ReviewCycle $reviewCycle): JsonResponse
    {
        $this->cycleService->delete($reviewCycle->id);

        return response()->json(['message' => 'Review cycle deleted successfully.'], Response::HTTP_OK);
    }

    public function activate(ReviewCycle $reviewCycle): ReviewCycleResource
    {
        $cycle = $this->cycleService->activate($reviewCycle->id);

        return new ReviewCycleResource($cycle->load('creator'));
    }

    public function complete(ReviewCycle $reviewCycle): ReviewCycleResource
    {
        $cycle = $this->cycleService->complete($reviewCycle->id);

        return new ReviewCycleResource($cycle->load('creator'));
    }
}

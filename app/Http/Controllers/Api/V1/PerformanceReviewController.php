<?php

namespace App\Http\Controllers\Api\V1;

use App\DTOs\PerformanceReviewDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\StorePerformanceReviewRequest;
use App\Http\Requests\SubmitReviewRequest;
use App\Http\Resources\PerformanceReviewResource;
use App\Models\PerformanceReview;
use App\Services\PerformanceReviewService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\Response;

class PerformanceReviewController extends Controller
{
    public function __construct(private PerformanceReviewService $reviewService)
    {
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $reviews = $this->reviewService->paginateWithFilters(
            $request->only([
                'company_id', 'review_cycle_id', 'employee_id', 'reviewer_id',
                'type', 'status', 'rating', 'sort_by', 'sort_dir',
            ]),
            $request->integer('per_page', 15)
        );

        return PerformanceReviewResource::collection($reviews);
    }

    public function store(StorePerformanceReviewRequest $request): JsonResponse
    {
        $dto = PerformanceReviewDTO::fromArray($request->validated());
        $review = $this->reviewService->create($dto);

        return (new PerformanceReviewResource($review->load(['employee', 'reviewer', 'reviewCycle', 'metrics'])))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(PerformanceReview $performanceReview): PerformanceReviewResource
    {
        $performanceReview->load(['employee', 'reviewer', 'reviewCycle', 'metrics']);

        return new PerformanceReviewResource($performanceReview);
    }

    public function submit(SubmitReviewRequest $request, PerformanceReview $performanceReview): PerformanceReviewResource
    {
        $review = $this->reviewService->submitReview(
            $performanceReview->id,
            $request->validated()
        );

        return new PerformanceReviewResource($review);
    }

    public function acknowledge(PerformanceReview $performanceReview): PerformanceReviewResource
    {
        $review = $this->reviewService->acknowledgeReview($performanceReview->id);

        return new PerformanceReviewResource($review);
    }
}

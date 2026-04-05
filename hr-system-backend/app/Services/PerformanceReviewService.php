<?php

namespace App\Services;

use App\DTOs\PerformanceReviewDTO;
use App\Enums\PerformanceRating;
use App\Enums\ReviewStatus;
use App\Events\ReviewSubmitted;
use App\Exceptions\BusinessException;
use App\Models\PerformanceReview;
use App\Repositories\Interfaces\PerformanceReviewRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class PerformanceReviewService extends BaseService
{
    public function __construct(
        protected PerformanceReviewRepositoryInterface $reviewRepository,
    ) {
        parent::__construct($reviewRepository);
    }

    public function create(PerformanceReviewDTO $dto): PerformanceReview
    {
        $data = $dto->toArray();
        $data['company_id'] = $data['company_id'] ?? auth()->user()->company_id;
        $data['status'] = ReviewStatus::Pending->value;

        return $this->reviewRepository->create($data);
    }

    public function submitReview(int $reviewId, array $submissionData): PerformanceReview
    {
        return DB::transaction(function () use ($reviewId, $submissionData) {
            $review = $this->reviewRepository->findOrFail($reviewId);

            if ($review->status === ReviewStatus::Submitted || $review->status === ReviewStatus::Acknowledged) {
                throw new BusinessException('This review has already been submitted.');
            }

            // Save metrics
            if (!empty($submissionData['metrics'])) {
                // Remove existing metrics and re-create
                $review->metrics()->delete();

                foreach ($submissionData['metrics'] as $metric) {
                    $review->metrics()->create([
                        'name' => $metric['name'],
                        'weight' => $metric['weight'],
                        'score' => $metric['score'],
                        'comments' => $metric['comments'] ?? null,
                    ]);
                }
            }

            // Calculate overall score: sum(score_i * weight_i) / sum(weight_i)
            $overallScore = $review->calculateOverallScore();
            $rating = $overallScore !== null ? PerformanceRating::fromScore($overallScore) : null;

            // Update review
            $review->update([
                'overall_score' => $overallScore,
                'rating' => $rating?->value,
                'status' => ReviewStatus::Submitted,
                'manager_comments' => $submissionData['manager_comments'] ?? $review->manager_comments,
                'goals_for_next_period' => $submissionData['goals_for_next_period'] ?? $review->goals_for_next_period,
                'development_plan' => $submissionData['development_plan'] ?? $review->development_plan,
                'promotion_recommendation' => $submissionData['promotion_recommendation'] ?? $review->promotion_recommendation,
                'submitted_at' => now(),
            ]);

            $review = $review->fresh(['metrics', 'employee', 'reviewer', 'reviewCycle']);

            event(new ReviewSubmitted($review));

            return $review;
        });
    }

    public function acknowledgeReview(int $reviewId): PerformanceReview
    {
        $review = $this->reviewRepository->findOrFail($reviewId);

        if ($review->status !== ReviewStatus::Submitted) {
            throw new BusinessException('Only submitted reviews can be acknowledged.');
        }

        $review->update([
            'status' => ReviewStatus::Acknowledged,
            'acknowledged_at' => now(),
        ]);

        return $review->fresh(['metrics', 'employee', 'reviewer', 'reviewCycle']);
    }

    public function getByEmployee(int $employeeId): Collection
    {
        return $this->reviewRepository->getByEmployee($employeeId);
    }

    public function paginateWithFilters(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        return $this->reviewRepository->paginateWithFilters($filters, $perPage);
    }
}

<?php

namespace App\DTOs;

readonly class PerformanceReviewDTO
{
    public function __construct(
        public ?int $companyId = null,
        public ?int $reviewCycleId = null,
        public ?int $employeeId = null,
        public ?int $reviewerId = null,
        public ?string $type = null,
        public ?string $status = null,
        public ?string $managerComments = null,
        public ?string $employeeComments = null,
        public ?string $goalsForNextPeriod = null,
        public ?string $developmentPlan = null,
        public ?bool $promotionRecommendation = null,
        public ?array $metrics = null,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            companyId: $data['company_id'] ?? null,
            reviewCycleId: $data['review_cycle_id'] ?? null,
            employeeId: $data['employee_id'] ?? null,
            reviewerId: $data['reviewer_id'] ?? null,
            type: $data['type'] ?? null,
            status: $data['status'] ?? null,
            managerComments: $data['manager_comments'] ?? null,
            employeeComments: $data['employee_comments'] ?? null,
            goalsForNextPeriod: $data['goals_for_next_period'] ?? null,
            developmentPlan: $data['development_plan'] ?? null,
            promotionRecommendation: $data['promotion_recommendation'] ?? null,
            metrics: $data['metrics'] ?? null,
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'company_id' => $this->companyId,
            'review_cycle_id' => $this->reviewCycleId,
            'employee_id' => $this->employeeId,
            'reviewer_id' => $this->reviewerId,
            'type' => $this->type,
            'status' => $this->status,
            'manager_comments' => $this->managerComments,
            'employee_comments' => $this->employeeComments,
            'goals_for_next_period' => $this->goalsForNextPeriod,
            'development_plan' => $this->developmentPlan,
            'promotion_recommendation' => $this->promotionRecommendation,
        ], fn ($value) => $value !== null);
    }
}

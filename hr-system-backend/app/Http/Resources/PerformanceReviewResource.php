<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PerformanceReviewResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'company_id' => $this->company_id,
            'review_cycle_id' => $this->review_cycle_id,
            'employee_id' => $this->employee_id,
            'reviewer_id' => $this->reviewer_id,
            'type' => $this->type,
            'overall_score' => $this->overall_score !== null ? (float) $this->overall_score : null,
            'rating' => $this->rating,
            'rating_label' => $this->rating ? \App\Enums\PerformanceRating::tryFrom($this->rating)?->label() : null,
            'status' => $this->status?->value,
            'status_label' => $this->status?->label(),
            'manager_comments' => $this->manager_comments,
            'employee_comments' => $this->employee_comments,
            'goals_for_next_period' => $this->goals_for_next_period,
            'development_plan' => $this->development_plan,
            'promotion_recommendation' => $this->promotion_recommendation,
            'submitted_at' => $this->submitted_at?->toISOString(),
            'acknowledged_at' => $this->acknowledged_at?->toISOString(),

            // Relationships
            'employee' => new EmployeeResource($this->whenLoaded('employee')),
            'reviewer' => new UserResource($this->whenLoaded('reviewer')),
            'review_cycle' => new ReviewCycleResource($this->whenLoaded('reviewCycle')),
            'metrics' => ReviewMetricResource::collection($this->whenLoaded('metrics')),

            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}

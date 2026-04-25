<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReviewCycleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'company_id' => $this->company_id,
            'name' => $this->name,
            'type' => $this->type?->value,
            'type_label' => $this->type?->label(),
            'start_date' => $this->start_date?->toDateString(),
            'end_date' => $this->end_date?->toDateString(),
            'status' => $this->status?->value,
            'status_label' => $this->status?->label(),
            'created_by' => $this->created_by,

            // Relationships
            'creator' => new UserResource($this->whenLoaded('creator')),
            'reviews' => PerformanceReviewResource::collection($this->whenLoaded('reviews')),
            'goals' => GoalResource::collection($this->whenLoaded('goals')),

            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}

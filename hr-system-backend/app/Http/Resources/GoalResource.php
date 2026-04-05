<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GoalResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'company_id' => $this->company_id,
            'employee_id' => $this->employee_id,
            'review_cycle_id' => $this->review_cycle_id,
            'title' => $this->title,
            'description' => $this->description,
            'target_value' => $this->target_value,
            'current_value' => $this->current_value,
            'unit' => $this->unit,
            'status' => $this->status?->value,
            'status_label' => $this->status?->label(),
            'due_date' => $this->due_date?->toDateString(),
            'progress_percentage' => $this->target_value > 0
                ? min(100, round(($this->current_value / $this->target_value) * 100, 2))
                : null,

            // Relationships
            'employee' => new EmployeeResource($this->whenLoaded('employee')),
            'review_cycle' => new ReviewCycleResource($this->whenLoaded('reviewCycle')),

            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}

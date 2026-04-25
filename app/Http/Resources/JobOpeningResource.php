<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class JobOpeningResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'company_id' => $this->company_id,
            'department_id' => $this->department_id,
            'title' => $this->title,
            'description' => $this->description,
            'requirements' => $this->requirements,
            'employment_type' => $this->employment_type,
            'location' => $this->location,
            'salary_range_min' => $this->salary_range_min,
            'salary_range_max' => $this->salary_range_max,
            'positions_count' => $this->positions_count,
            'status' => $this->status?->value,
            'status_label' => $this->status?->label(),
            'created_by' => $this->created_by,
            'published_at' => $this->published_at?->toISOString(),
            'closes_at' => $this->closes_at?->toISOString(),

            // Relationships
            'department' => $this->whenLoaded('department'),
            'creator' => new UserResource($this->whenLoaded('creator')),
            'candidates' => CandidateResource::collection($this->whenLoaded('candidates')),
            'candidates_count' => $this->when($this->candidates_count !== null, $this->candidates_count),

            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}

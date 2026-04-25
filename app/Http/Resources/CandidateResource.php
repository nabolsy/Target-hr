<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CandidateResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'company_id' => $this->company_id,
            'job_opening_id' => $this->job_opening_id,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'full_name' => $this->full_name,
            'email' => $this->email,
            'phone' => $this->phone,
            'cv_path' => $this->cv_path,
            'cover_letter' => $this->cover_letter,
            'stage' => $this->stage?->value,
            'stage_label' => $this->stage?->label(),
            'status' => $this->status?->value,
            'status_label' => $this->status?->label(),
            'source' => $this->source,
            'applied_at' => $this->applied_at?->toISOString(),
            'notes' => $this->notes,

            // Relationships
            'job_opening' => new JobOpeningResource($this->whenLoaded('jobOpening')),
            'interviews' => InterviewResource::collection($this->whenLoaded('interviews')),

            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}

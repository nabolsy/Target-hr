<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InterviewResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'company_id' => $this->company_id,
            'candidate_id' => $this->candidate_id,
            'interviewer_id' => $this->interviewer_id,
            'scheduled_at' => $this->scheduled_at?->toISOString(),
            'duration_minutes' => $this->duration_minutes,
            'type' => $this->type?->value,
            'type_label' => $this->type?->label(),
            'location' => $this->location,
            'status' => $this->status,
            'notes' => $this->notes,

            // Relationships
            'candidate' => new CandidateResource($this->whenLoaded('candidate')),
            'interviewer' => new UserResource($this->whenLoaded('interviewer')),
            'feedback' => InterviewFeedbackResource::collection($this->whenLoaded('feedback')),

            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}

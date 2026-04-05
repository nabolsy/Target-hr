<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InterviewFeedbackResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'interview_id' => $this->interview_id,
            'user_id' => $this->user_id,
            'rating' => $this->rating,
            'strengths' => $this->strengths,
            'weaknesses' => $this->weaknesses,
            'recommendation' => $this->recommendation,
            'comments' => $this->comments,

            // Relationships
            'user' => new UserResource($this->whenLoaded('user')),
            'interview' => new InterviewResource($this->whenLoaded('interview')),

            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}

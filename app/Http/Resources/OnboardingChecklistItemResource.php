<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OnboardingChecklistItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'checklist_id' => $this->checklist_id,
            'title' => $this->title,
            'description' => $this->description,
            'is_required' => $this->is_required,
            'is_completed' => $this->is_completed,
            'completed_by' => $this->completed_by,
            'completed_at' => $this->completed_at?->toISOString(),
            'assigned_to' => $this->assigned_to,
            'due_date' => $this->due_date?->toDateString(),
            'notes' => $this->notes,
            'sort_order' => $this->sort_order,

            'assigned_user' => new UserResource($this->whenLoaded('assignedTo')),
            'completed_by_user' => new UserResource($this->whenLoaded('completedBy')),

            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}

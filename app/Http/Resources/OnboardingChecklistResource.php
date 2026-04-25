<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OnboardingChecklistResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $totalItems = $this->whenLoaded('items', fn () => $this->items->count(), 0);
        $completedItems = $this->whenLoaded('items', fn () => $this->items->where('is_completed', true)->count(), 0);

        return [
            'id' => $this->id,
            'company_id' => $this->company_id,
            'employee_id' => $this->employee_id,
            'template_id' => $this->template_id,
            'type' => $this->type->value,
            'type_label' => $this->type->label(),
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'started_at' => $this->started_at?->toISOString(),
            'completed_at' => $this->completed_at?->toISOString(),
            'created_by' => $this->created_by,

            'progress' => [
                'completed' => $completedItems,
                'total' => $totalItems,
                'percentage' => $totalItems > 0 ? round(($completedItems / $totalItems) * 100, 2) : 0,
            ],

            'employee' => new EmployeeResource($this->whenLoaded('employee')),
            'template' => new OnboardingTemplateResource($this->whenLoaded('template')),
            'creator' => new UserResource($this->whenLoaded('creator')),
            'items' => OnboardingChecklistItemResource::collection($this->whenLoaded('items')),

            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}

<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TaskResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $checklistTotal = 0;
        $checklistCompleted = 0;

        if ($this->relationLoaded('checklists')) {
            foreach ($this->checklists as $checklist) {
                if ($checklist->relationLoaded('items')) {
                    $checklistTotal += $checklist->items->count();
                    $checklistCompleted += $checklist->items->where('is_completed', true)->count();
                }
            }
        }

        return [
            'id' => $this->id,
            'company_id' => $this->company_id,
            'board_id' => $this->board_id,
            'column_id' => $this->column_id,
            'column_name' => $this->whenLoaded('column', fn () => $this->column->name),
            'title' => $this->title,
            'description' => $this->description,
            'creator_id' => $this->creator_id,
            'creator' => new UserResource($this->whenLoaded('creator')),
            'priority' => $this->priority?->value,
            'priority_label' => $this->priority?->label(),
            'start_date' => $this->start_date?->format('Y-m-d'),
            'due_date' => $this->due_date?->format('Y-m-d'),
            'estimated_hours' => $this->estimated_hours ? (float) $this->estimated_hours : null,
            'actual_hours' => $this->actual_hours ? (float) $this->actual_hours : null,
            'completion_percentage' => $this->completion_percentage,
            'sort_order' => $this->sort_order,
            'is_archived' => $this->is_archived,
            'assignees' => EmployeeResource::collection($this->whenLoaded('assignees')),
            'labels' => $this->whenLoaded('labels'),
            'comments_count' => $this->when(
                $this->relationLoaded('comments') || isset($this->comments_count),
                fn () => $this->comments_count ?? $this->comments->count()
            ),
            'attachments_count' => $this->when(
                $this->relationLoaded('attachments') || isset($this->attachments_count),
                fn () => $this->attachments_count ?? $this->attachments->count()
            ),
            'checklist_progress' => $this->when(
                $this->relationLoaded('checklists'),
                fn () => [
                    'completed' => $checklistCompleted,
                    'total' => $checklistTotal,
                ]
            ),
            'board' => new BoardResource($this->whenLoaded('board')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}

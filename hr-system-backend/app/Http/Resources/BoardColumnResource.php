<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BoardColumnResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'board_id' => $this->board_id,
            'name' => $this->name,
            'sort_order' => $this->sort_order,
            'color' => $this->color,
            'wip_limit' => $this->wip_limit,
            'is_done_column' => $this->is_done_column,
            'archived_at' => $this->archived_at?->toISOString(),
            'tasks' => TaskResource::collection($this->whenLoaded('tasks')),
            'tasks_count' => $this->when(isset($this->tasks_count), $this->tasks_count),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}

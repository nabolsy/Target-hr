<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BoardResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'company_id' => $this->company_id,
            'name' => $this->name,
            'description' => $this->description,
            'color' => $this->color,
            'department_id' => $this->department_id,
            'type' => $this->type->value,
            'type_label' => $this->type->label(),
            'is_archived' => $this->is_archived,
            'created_by' => $this->created_by,
            'creator' => new UserResource($this->whenLoaded('creator')),
            'department' => $this->whenLoaded('department'),
            'columns' => BoardColumnResource::collection($this->whenLoaded('columns')),
            'members' => $this->whenLoaded('members', fn () => $this->members->map(fn ($m) => [
                'id' => $m->id,
                'first_name' => $m->first_name,
                'last_name' => $m->last_name,
                'full_name' => trim("{$m->first_name} {$m->last_name}"),
                'email' => $m->email,
                'profile_image' => $m->profile_image,
            ])),
            'tasks_count' => $this->when(isset($this->tasks_count), $this->tasks_count),
            'members_count' => $this->when(isset($this->members_count), $this->members_count),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}

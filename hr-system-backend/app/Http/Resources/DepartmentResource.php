<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DepartmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'company_id' => $this->company_id,
            'parent_id' => $this->parent_id,
            'name' => $this->name,
            'description' => $this->description,
            'manager_id' => $this->manager_id,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'is_active' => $this->is_active,
            'children_count' => $this->whenCounted('children'),
            'employees_count' => $this->whenCounted('employees'),
            'parent' => new DepartmentResource($this->whenLoaded('parent')),
            'manager' => $this->whenLoaded('manager', fn () => [
                'id' => $this->manager->id,
                'name' => $this->manager->name,
                'email' => $this->manager->email,
            ]),
            'children' => DepartmentResource::collection($this->whenLoaded('children')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}

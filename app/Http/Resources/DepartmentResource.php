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
            'name_ar' => $this->name_ar,
            'code' => $this->code,
            'description' => $this->description,
            'manager_id' => $this->manager_id,
            'branch_id' => $this->branch_id,
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
            'branch' => $this->whenLoaded('branch', fn () => [
                'id' => $this->branch->id,
                'name' => $this->branch->name,
                'city' => $this->branch->city,
                'country' => $this->branch->country,
                'is_headquarters' => $this->branch->is_headquarters,
            ]),
            'children' => DepartmentResource::collection($this->whenLoaded('children')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}

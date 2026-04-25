<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DesignationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'company_id' => $this->company_id,
            'department_id' => $this->department_id,
            'department' => $this->whenLoaded('department', fn () => $this->department ? [
                'id' => $this->department->id,
                'name' => $this->department->name,
            ] : null),
            'name' => $this->name,
            'name_ar' => $this->name_ar,
            'description' => $this->description,
            'level' => $this->level,
            'grade' => $this->grade,
            'is_active' => (bool) ($this->is_active ?? true),
            'employees_count' => $this->whenCounted('employees'),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}

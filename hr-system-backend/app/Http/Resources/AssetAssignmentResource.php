<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AssetAssignmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'asset_id' => $this->asset_id,
            'employee_id' => $this->employee_id,
            'assigned_by' => $this->assigned_by,
            'assigned_at' => $this->assigned_at?->toISOString(),
            'returned_at' => $this->returned_at?->toISOString(),
            'condition_on_assign' => $this->condition_on_assign,
            'condition_on_return' => $this->condition_on_return,
            'notes' => $this->notes,

            'asset' => new AssetResource($this->whenLoaded('asset')),
            'employee' => new EmployeeResource($this->whenLoaded('employee')),
            'assigned_by_user' => new UserResource($this->whenLoaded('assignedBy')),

            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}

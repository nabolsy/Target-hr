<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AssetResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'company_id' => $this->company_id,
            'name' => $this->name,
            'asset_code' => $this->asset_code,
            'category' => $this->category?->value,
            'category_label' => $this->category?->label(),
            'description' => $this->description,
            'serial_number' => $this->serial_number,
            'purchase_date' => $this->purchase_date?->toDateString(),
            'purchase_cost' => $this->purchase_cost,
            'condition' => $this->condition?->value,
            'condition_label' => $this->condition?->label(),
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'location' => $this->location,
            'notes' => $this->notes,

            'current_assignment' => new AssetAssignmentResource($this->whenLoaded('currentAssignment')),
            'current_employee' => new EmployeeResource($this->whenLoaded('currentEmployee')),

            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}

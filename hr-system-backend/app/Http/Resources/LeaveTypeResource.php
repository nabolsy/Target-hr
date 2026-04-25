<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LeaveTypeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'company_id' => $this->company_id,
            'name' => $this->name,
            'name_ar' => $this->name_ar,
            'slug' => $this->slug,
            'days_per_year' => (float) $this->days_per_year,
            // Alias so the frontend can rely on whichever key feels natural.
            'days_allowed' => (float) $this->days_per_year,
            'is_paid' => $this->is_paid,
            'requires_attachment' => $this->requires_attachment,
            'allows_half_day' => (bool) $this->allows_half_day,
            'is_active' => $this->is_active,
            'description' => $this->description,
            'color' => $this->color,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}

<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CompanyBranchResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'company_id' => $this->company_id,
            'name' => $this->name,
            'name_ar' => $this->name_ar,
            'address' => $this->address,
            'city' => $this->city,
            'state' => $this->state,
            'country' => $this->country,
            'postal_code' => $this->postal_code,
            'phone' => $this->phone,
            'email' => $this->email,
            'manager_id' => $this->manager_id,
            'manager' => $this->whenLoaded('manager', fn () => $this->manager ? [
                'id' => $this->manager->id,
                'first_name' => $this->manager->first_name,
                'last_name' => $this->manager->last_name,
                'full_name' => trim("{$this->manager->first_name} {$this->manager->last_name}"),
                'email' => $this->manager->email,
            ] : null),
            'is_headquarters' => (bool) $this->is_headquarters,
            'is_active' => (bool) $this->is_active,
            'employees_count' => $this->whenCounted('employees'),
            'departments_count' => $this->whenCounted('departments'),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}

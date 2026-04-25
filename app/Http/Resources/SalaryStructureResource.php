<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SalaryStructureResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $canViewSalary = auth()->check() && auth()->user()->can('view_salary');

        $data = [
            'id' => $this->id,
            'company_id' => $this->company_id,
            'employee_id' => $this->employee_id,
            'currency' => $this->currency,
            'payment_frequency' => $this->payment_frequency->value,
            'payment_frequency_label' => $this->payment_frequency->label(),
            'effective_date' => $this->effective_date?->toDateString(),

            'basic_salary' => $this->when($canViewSalary, $this->basic_salary),

            'employee' => new EmployeeResource($this->whenLoaded('employee')),
            'components' => $this->whenLoaded('components', function () use ($canViewSalary) {
                return $this->components->map(function ($component) use ($canViewSalary) {
                    $item = [
                        'id' => $component->id,
                        'name' => $component->name,
                        'type' => $component->type->value,
                        'type_label' => $component->type->label(),
                        'is_percentage' => $component->is_percentage,
                        'is_taxable' => $component->is_taxable,
                        'sort_order' => $component->sort_order,
                    ];

                    if ($canViewSalary) {
                        $item['amount'] = $component->amount;
                    }

                    return $item;
                });
            }),

            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];

        return $data;
    }
}

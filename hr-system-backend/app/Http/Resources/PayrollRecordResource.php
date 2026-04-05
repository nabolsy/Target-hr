<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PayrollRecordResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $canViewSalary = auth()->check() && auth()->user()->can('view_salary');

        $data = [
            'id' => $this->id,
            'company_id' => $this->company_id,
            'payroll_period_id' => $this->payroll_period_id,
            'employee_id' => $this->employee_id,
            'working_days' => $this->working_days,
            'present_days' => $this->present_days,
            'absent_days' => $this->absent_days,
            'leave_days' => $this->leave_days,
            'overtime_hours' => $this->overtime_hours,
            'notes' => $this->notes,

            'basic_salary' => $this->when($canViewSalary, $this->basic_salary),
            'total_allowances' => $this->when($canViewSalary, $this->total_allowances),
            'total_deductions' => $this->when($canViewSalary, $this->total_deductions),
            'gross_salary' => $this->when($canViewSalary, $this->gross_salary),
            'net_salary' => $this->when($canViewSalary, $this->net_salary),

            'employee' => new EmployeeResource($this->whenLoaded('employee')),
            'payroll_period' => new PayrollPeriodResource($this->whenLoaded('payrollPeriod')),

            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];

        return $data;
    }
}

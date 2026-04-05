<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AttendanceRecordResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'company_id' => $this->company_id,
            'employee_id' => $this->employee_id,
            'employee' => $this->whenLoaded('employee', fn () => [
                'id' => $this->employee->id,
                'first_name' => $this->employee->first_name,
                'last_name' => $this->employee->last_name,
                'employee_id_number' => $this->employee->employee_id_number,
            ]),
            'date' => $this->date->toDateString(),
            'check_in' => $this->check_in?->toISOString(),
            'check_out' => $this->check_out?->toISOString(),
            'shift' => new ShiftResource($this->whenLoaded('shift')),
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'worked_hours' => $this->worked_hours,
            'overtime_hours' => $this->overtime_hours,
            'break_minutes' => $this->break_minutes,
            'notes' => $this->notes,
            'ip_address' => $this->ip_address,
            'adjustment_requests' => $this->whenLoaded('adjustmentRequests'),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}

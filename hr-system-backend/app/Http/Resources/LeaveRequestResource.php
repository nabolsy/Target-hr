<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LeaveRequestResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'company_id' => $this->company_id,
            'employee_id' => $this->employee_id,
            // NB: the `employee` relationship is an Employee model, NOT
            // a User. Wrapping it in UserResource crashes because
            // UserResource calls getRoleNames() which is a Spatie
            // method on User. Inline a minimal employee payload here
            // instead; the frontend only needs name + department for
            // the leave-requests table.
            'employee' => $this->whenLoaded('employee', fn () => $this->employee ? [
                'id'            => $this->employee->id,
                'first_name'    => $this->employee->first_name,
                'last_name'     => $this->employee->last_name,
                'full_name'     => trim("{$this->employee->first_name} {$this->employee->last_name}"),
                'email'         => $this->employee->email,
                'profile_image' => $this->employee->profile_image,
                'department_id' => $this->employee->department_id,
                'department'    => $this->employee->relationLoaded('department') && $this->employee->department
                    ? ['id' => $this->employee->department->id, 'name' => $this->employee->department->name]
                    : null,
            ] : null),
            'leave_type_id' => $this->leave_type_id,
            'leave_type' => new LeaveTypeResource($this->whenLoaded('leaveType')),
            'start_date' => $this->start_date?->format('Y-m-d'),
            'end_date' => $this->end_date?->format('Y-m-d'),
            'is_half_day' => $this->is_half_day,
            'duration_type' => $this->duration_type ?? 'full',
            'total_days' => (float) $this->total_days,
            'reason' => $this->reason,
            'attachment_path' => $this->attachment_path,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'approved_by' => $this->approved_by,
            'approver' => new UserResource($this->whenLoaded('approver')),
            'approved_at' => $this->approved_at?->toISOString(),
            'rejection_reason' => $this->rejection_reason,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}

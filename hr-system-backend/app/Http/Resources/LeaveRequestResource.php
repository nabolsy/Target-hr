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
            'employee' => new UserResource($this->whenLoaded('employee')),
            'leave_type_id' => $this->leave_type_id,
            'leave_type' => new LeaveTypeResource($this->whenLoaded('leaveType')),
            'start_date' => $this->start_date?->format('Y-m-d'),
            'end_date' => $this->end_date?->format('Y-m-d'),
            'is_half_day' => $this->is_half_day,
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

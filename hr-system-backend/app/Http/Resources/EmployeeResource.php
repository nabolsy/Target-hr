<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EmployeeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $data = [
            'id' => $this->id,
            'company_id' => $this->company_id,
            'user_id' => $this->user_id,
            'department_id' => $this->department_id,
            'designation_id' => $this->designation_id,
            'manager_id' => $this->manager_id,
            'employee_id_number' => $this->employee_id_number,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'full_name' => $this->full_name,
            'email' => $this->email,
            'phone' => $this->phone,
            'date_of_birth' => $this->date_of_birth?->toDateString(),
            'gender' => $this->gender?->value,
            'gender_label' => $this->gender?->label(),
            'national_id' => $this->national_id,
            'address' => $this->address,
            'city' => $this->city,
            'state' => $this->state,
            'country' => $this->country,
            'postal_code' => $this->postal_code,
            'profile_image' => $this->profile_image,
            'employment_type' => $this->employment_type->value,
            'employment_type_label' => $this->employment_type->label(),
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'join_date' => $this->join_date?->toDateString(),
            'probation_end_date' => $this->probation_end_date?->toDateString(),
            'work_location' => $this->work_location,
            'emergency_contact_name' => $this->emergency_contact_name,
            'emergency_contact_phone' => $this->emergency_contact_phone,
            'emergency_contact_relation' => $this->emergency_contact_relation,
            'notes' => $this->notes,

            // Conditional salary/bank fields - only visible with 'view_salary' permission
            'salary' => $this->when(
                auth()->check() && auth()->user()->can('view_salary'),
                $this->salary
            ),
            'bank_name' => $this->when(
                auth()->check() && auth()->user()->can('view_salary'),
                $this->bank_name
            ),
            'bank_account_number' => $this->when(
                auth()->check() && auth()->user()->can('view_salary'),
                $this->bank_account_number
            ),

            // Relationships (when loaded)
            'company' => new CompanyResource($this->whenLoaded('company')),
            'user' => new UserResource($this->whenLoaded('user')),
            'department' => $this->whenLoaded('department'),
            'designation' => $this->whenLoaded('designation'),
            'manager' => new EmployeeResource($this->whenLoaded('manager')),
            'subordinates' => EmployeeResource::collection($this->whenLoaded('subordinates')),
            'attendance' => $this->whenLoaded('attendance'),
            'leave_requests' => $this->whenLoaded('leaveRequests'),
            'documents' => $this->whenLoaded('documents'),
            'tasks' => $this->whenLoaded('tasks'),

            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];

        return $data;
    }
}

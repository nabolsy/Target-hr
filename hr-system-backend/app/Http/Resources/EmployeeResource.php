<?php

namespace App\Http\Resources;

use App\Services\Access\PermissionService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EmployeeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        // Confidential-field visibility:
        //   - The viewer looking at their own record (self), OR
        //   - The viewer has `employee.update` at any scope (HR / manager).
        //
        // Everyone else still sees the public fields (name, email, job
        // title, department, status, etc.) but the personal ones are
        // stripped. This is the Step 7 employee-self-service tightening.
        $viewer = auth()->user();
        $showConfidential = $this->canSeeConfidential($viewer);

        // Salary / bank fields preserve the existing legacy permission
        // but ALSO unlock for self. Keeps current HR access intact.
        $showSalary = $showConfidential && $viewer && (
            $viewer->can('view_salary') || $viewer->id === $this->user_id
        );

        $data = [
            // Always visible — identity + work context.
            'id' => $this->id,
            'company_id' => $this->company_id,
            'user_id' => $this->user_id,
            'department_id' => $this->department_id,
            'branch_id' => $this->branch_id,
            'designation_id' => $this->designation_id,
            'manager_id' => $this->manager_id,
            'employee_id_number' => $this->employee_id_number,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'full_name' => $this->full_name,
            'email' => $this->email,
            'profile_image' => $this->profile_image,
            // Null-safe because legacy/imported rows (and newly-created
            // records mid-save) may not have these enum columns set.
            // Falling back to `null` lets the frontend handle the empty
            // state instead of 500-ing the whole list endpoint.
            'employment_type' => $this->employment_type?->value,
            'employment_type_label' => $this->employment_type?->label(),
            'status' => $this->status?->value,
            'status_label' => $this->status?->label(),
            'join_date' => $this->join_date?->toDateString(),
            'work_location' => $this->work_location,

            // Confidential — only for self / HR / managers.
            'phone' => $this->when($showConfidential, $this->phone),
            'date_of_birth' => $this->when($showConfidential, $this->date_of_birth?->toDateString()),
            'gender' => $this->when($showConfidential, $this->gender?->value),
            'gender_label' => $this->when($showConfidential, $this->gender?->label()),
            'national_id' => $this->when($showConfidential, $this->national_id),
            'address' => $this->when($showConfidential, $this->address),
            'city' => $this->when($showConfidential, $this->city),
            'state' => $this->when($showConfidential, $this->state),
            'country' => $this->when($showConfidential, $this->country),
            'postal_code' => $this->when($showConfidential, $this->postal_code),
            'probation_end_date' => $this->when($showConfidential, $this->probation_end_date?->toDateString()),
            'emergency_contact_name' => $this->when($showConfidential, $this->emergency_contact_name),
            'emergency_contact_phone' => $this->when($showConfidential, $this->emergency_contact_phone),
            'emergency_contact_relation' => $this->when($showConfidential, $this->emergency_contact_relation),
            'notes' => $this->when($showConfidential, $this->notes),

            // Salary + banking — most sensitive, guarded by legacy gate.
            'salary' => $this->when($showSalary, $this->salary),
            'bank_name' => $this->when($showSalary, $this->bank_name),
            'bank_account_number' => $this->when($showSalary, $this->bank_account_number),

            // Relationships (when loaded)
            'company' => new CompanyResource($this->whenLoaded('company')),
            'user' => new UserResource($this->whenLoaded('user')),
            'department' => $this->whenLoaded('department'),
            'designation' => $this->whenLoaded('designation', fn () => $this->designation ? [
                'id' => $this->designation->id,
                'name' => $this->designation->name,
                'name_ar' => $this->designation->name_ar,
                'grade' => $this->designation->grade,
                'level' => $this->designation->level,
                'department_id' => $this->designation->department_id,
            ] : null),
            'branch' => $this->whenLoaded('branch', fn () => $this->branch ? [
                'id' => $this->branch->id,
                'name' => $this->branch->name,
                'name_ar' => $this->branch->name_ar,
                'city' => $this->branch->city,
                'country' => $this->branch->country,
            ] : null),
            'manager' => new EmployeeResource($this->whenLoaded('manager')),
            'subordinates' => EmployeeResource::collection($this->whenLoaded('subordinates')),
            'attendance' => $this->whenLoaded('attendance'),
            'leave_requests' => $this->whenLoaded('leaveRequests'),
            'documents' => $this->whenLoaded('documents'),
            'tasks' => $this->whenLoaded('tasks'),

            // All department memberships via the employee_department pivot.
            // The `department` field above is still the authoritative primary;
            // this list exposes historical / multi-dept context when loaded.
            'departments' => $this->whenLoaded('departments', fn () => $this->departments->map(fn ($d) => [
                'id'           => $d->id,
                'name'         => $d->name,
                'name_ar'      => $d->name_ar,
                'code'         => $d->code,
                'is_primary'   => (bool) $d->pivot->is_primary,
                'start_date'   => $d->pivot->start_date,
                'end_date'     => $d->pivot->end_date,
                'role'         => $d->pivot->role,
            ])),

            // Signal to the frontend whether the viewer got the redacted
            // version — useful to show a "contact HR for personal info"
            // affordance on pages that render colleague profiles.
            'is_confidential_visible' => $showConfidential,

            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];

        return $data;
    }

    /**
     * Decide whether confidential fields are included in this serialisation.
     *
     * Rules (first match wins):
     *   1. No auth user → hide (defensive; should never happen inside
     *      the auth:sanctum middleware).
     *   2. Super Admin / the record's own user → show.
     *   3. Viewer has employee.update at any scope (HR / dept manager) AND
     *      the record is within their visible-employee set → show.
     *   4. Otherwise → hide.
     */
    private function canSeeConfidential(?object $viewer): bool
    {
        if (! $viewer) {
            return false;
        }

        // Self-access always wins.
        if ((int) $viewer->id === (int) $this->user_id) {
            return true;
        }

        // Super admin via the legacy enum before()-style shortcut.
        if (method_exists($viewer, 'isSuperAdmin') && $viewer->isSuperAdmin()) {
            return true;
        }

        // HR / manager tier: must have employee.update permission AND the
        // record must be within the visible-employee set for that scope.
        $permissions = app(PermissionService::class);
        $scope = $permissions->getScope($viewer, 'employee.update');
        if ($scope === null) {
            return false;
        }

        if ($scope === 'company') {
            return true;
        }

        $visible = $permissions->visibleEmployeeIds($viewer, 'employee.update');
        if ($visible === null) {
            return true;
        }

        return in_array((int) $this->id, $visible, true);
    }
}

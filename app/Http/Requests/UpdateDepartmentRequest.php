<?php

namespace App\Http\Requests;

use App\Enums\DepartmentStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateDepartmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Department edits are always within the caller's own company —
     * you can't move a department across tenants. So instead of
     * forcing the frontend to thread company_id through every edit
     * payload, default it from the authenticated user here. This
     * mirrors StoreDesignationRequest's pattern and keeps the
     * controller + service layer happy.
     */
    protected function prepareForValidation(): void
    {
        if (! $this->has('company_id') && $this->user()) {
            $this->merge(['company_id' => $this->user()->company_id]);
        }
    }

    public function rules(): array
    {
        $departmentId = $this->route('department');
        $id = is_object($departmentId) ? $departmentId->id : $departmentId;

        return [
            // `sometimes` because prepareForValidation auto-fills from
            // auth()->user()->company_id when omitted.
            'company_id' => ['sometimes', 'required', 'integer', 'exists:companies,id'],
            'parent_id' => ['nullable', 'integer', 'exists:departments,id', 'different:id'],
            'name' => ['required', 'string', 'max:255'],
            'name_ar' => ['nullable', 'string', 'max:255'],
            'code' => [
                'nullable', 'string', 'max:50',
                Rule::unique('departments', 'code')
                    ->where(fn ($q) => $q->where('company_id', $this->input('company_id'))->whereNull('deleted_at'))
                    ->ignore($id),
            ],
            'description' => ['nullable', 'string', 'max:1000'],
            'manager_id' => ['nullable', 'integer', 'exists:users,id'],
            'branch_id' => ['nullable', 'integer', 'exists:company_branches,id'],
            'status' => ['nullable', Rule::enum(DepartmentStatus::class)],
        ];
    }
}

<?php

namespace App\Http\Requests;

use App\Enums\DepartmentStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDepartmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Default company_id from the auth user so the frontend form
     * doesn't have to pass it. Departments are strictly tenant-scoped
     * — the only company a user can create a department in is their
     * own, which the BelongsToTenant trait enforces anyway.
     */
    protected function prepareForValidation(): void
    {
        if (! $this->has('company_id') && $this->user()) {
            $this->merge(['company_id' => $this->user()->company_id]);
        }
    }

    public function rules(): array
    {
        return [
            'company_id' => ['sometimes', 'required', 'integer', 'exists:companies,id'],
            'parent_id' => ['nullable', 'integer', 'exists:departments,id'],
            'name' => ['required', 'string', 'max:255'],
            'name_ar' => ['nullable', 'string', 'max:255'],
            'code' => [
                'nullable', 'string', 'max:50',
                Rule::unique('departments', 'code')
                    ->where(fn ($q) => $q->where('company_id', $this->input('company_id'))->whereNull('deleted_at')),
            ],
            'description' => ['nullable', 'string', 'max:1000'],
            'manager_id' => ['nullable', 'integer', 'exists:users,id'],
            'branch_id' => ['nullable', 'integer', 'exists:company_branches,id'],
            'status' => ['nullable', Rule::enum(DepartmentStatus::class)],
        ];
    }
}

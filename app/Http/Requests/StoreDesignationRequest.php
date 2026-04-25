<?php

namespace App\Http\Requests;

use App\Models\Designation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDesignationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        // Default the company_id to the caller's company so the
        // frontend doesn't need to thread it through every form.
        if (! $this->has('company_id') && $this->user()) {
            $this->merge(['company_id' => $this->user()->company_id]);
        }
    }

    public function rules(): array
    {
        return [
            'company_id' => ['required', 'integer', 'exists:companies,id'],
            'name' => ['required', 'string', 'max:255'],
            'name_ar' => ['nullable', 'string', 'max:255'],
            'department_id' => ['nullable', 'integer', 'exists:departments,id'],
            'description' => ['nullable', 'string', 'max:1000'],
            'grade' => ['nullable', Rule::in(array_keys(Designation::GRADES))],
            'level' => ['nullable', 'integer', 'min:1', 'max:10'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}

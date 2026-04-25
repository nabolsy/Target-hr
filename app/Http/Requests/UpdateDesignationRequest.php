<?php

namespace App\Http\Requests;

use App\Models\Designation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateDesignationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'company_id' => ['sometimes', 'integer', 'exists:companies,id'],
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'name_ar' => ['nullable', 'string', 'max:255'],
            'department_id' => ['nullable', 'integer', 'exists:departments,id'],
            'description' => ['nullable', 'string', 'max:1000'],
            'grade' => ['nullable', Rule::in(array_keys(Designation::GRADES))],
            'level' => ['nullable', 'integer', 'min:1', 'max:10'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}

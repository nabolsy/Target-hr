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

    public function rules(): array
    {
        return [
            'company_id' => ['required', 'integer', 'exists:companies,id'],
            'parent_id' => ['nullable', 'integer', 'exists:departments,id'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'manager_id' => ['nullable', 'integer', 'exists:users,id'],
            'status' => ['nullable', Rule::enum(DepartmentStatus::class)],
        ];
    }
}

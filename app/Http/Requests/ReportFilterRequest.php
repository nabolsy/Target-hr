<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReportFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'start_date' => ['sometimes', 'nullable', 'date'],
            'end_date' => ['sometimes', 'nullable', 'date', 'after_or_equal:start_date'],
            'department_id' => ['sometimes', 'nullable', 'integer', 'exists:departments,id'],
            'status' => ['sometimes', 'nullable', 'string'],
            'employment_type' => ['sometimes', 'nullable', 'string'],
            'year' => ['sometimes', 'nullable', 'integer', 'min:2000', 'max:2100'],
            'month' => ['sometimes', 'nullable', 'integer', 'min:1', 'max:12'],
            'days' => ['sometimes', 'nullable', 'integer', 'min:1', 'max:365'],
        ];
    }
}

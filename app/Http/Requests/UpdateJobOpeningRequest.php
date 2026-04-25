<?php

namespace App\Http\Requests;

use App\Enums\EmploymentType;
use App\Enums\JobOpeningStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateJobOpeningRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'string', 'max:255'],
            'description' => ['sometimes', 'string', 'max:10000'],
            'requirements' => ['nullable', 'string', 'max:10000'],
            'department_id' => ['nullable', 'integer', 'exists:departments,id'],
            'employment_type' => ['sometimes', Rule::enum(EmploymentType::class)],
            'location' => ['nullable', 'string', 'max:255'],
            'salary_range_min' => ['nullable', 'numeric', 'min:0', 'max:9999999999.99'],
            'salary_range_max' => ['nullable', 'numeric', 'min:0', 'max:9999999999.99', 'gte:salary_range_min'],
            'positions_count' => ['sometimes', 'integer', 'min:1'],
            'status' => ['sometimes', Rule::enum(JobOpeningStatus::class)],
            'closes_at' => ['nullable', 'date'],
        ];
    }
}

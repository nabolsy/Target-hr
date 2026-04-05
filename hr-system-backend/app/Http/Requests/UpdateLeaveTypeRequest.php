<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateLeaveTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
            'days_per_year' => ['sometimes', 'required', 'numeric', 'min:0', 'max:365'],
            'is_paid' => ['sometimes', 'boolean'],
            'requires_attachment' => ['sometimes', 'boolean'],
            'is_active' => ['sometimes', 'boolean'],
            'description' => ['nullable', 'string', 'max:1000'],
        ];
    }
}

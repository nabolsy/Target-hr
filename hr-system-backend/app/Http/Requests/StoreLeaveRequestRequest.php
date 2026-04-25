<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreLeaveRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'leave_type_id' => ['required', 'integer', 'exists:leave_types,id'],
            'start_date' => ['required', 'date', 'after_or_equal:today'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'is_half_day' => ['sometimes', 'boolean'],
            // New canonical field. When set to any half-day variant the
            // service forces end_date = start_date and total_days = 0.5.
            'duration_type' => ['sometimes', 'string', 'in:full,first_half,second_half'],
            'reason' => ['required', 'string', 'max:1000'],
            'attachment' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,doc,docx', 'max:5120'],
        ];
    }

    public function messages(): array
    {
        return [
            'start_date.after_or_equal' => 'Leave start date must be today or a future date.',
            'end_date.after_or_equal' => 'Leave end date must be on or after the start date.',
            'attachment.max' => 'The attachment must not exceed 5MB.',
        ];
    }
}

<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AdjustmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'attendance_record_id' => ['required', 'integer', 'exists:attendance_records,id'],
            'requested_check_in' => ['nullable', 'date', 'required_without:requested_check_out'],
            'requested_check_out' => ['nullable', 'date', 'required_without:requested_check_in'],
            'reason' => ['required', 'string', 'max:2000'],
        ];
    }
}

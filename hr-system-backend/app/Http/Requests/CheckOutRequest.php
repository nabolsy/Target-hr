<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CheckOutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'attendance_record_id' => ['required', 'integer', 'exists:attendance_records,id'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}

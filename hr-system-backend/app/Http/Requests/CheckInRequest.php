<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CheckInRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'employee_id' => ['required', 'integer', 'exists:employees,id'],
            'shift_id' => ['nullable', 'integer', 'exists:shifts,id'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'ip_address' => ['nullable', 'ip'],
        ];
    }
}

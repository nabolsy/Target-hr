<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCompanySettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'work_start_time' => ['sometimes', 'date_format:H:i'],
            'work_end_time' => ['sometimes', 'date_format:H:i', 'after:work_start_time'],
            'work_days' => ['sometimes', 'array'],
            'work_days.*' => ['string', 'in:sunday,monday,tuesday,wednesday,thursday,friday,saturday'],
            'timezone' => ['sometimes', 'string', 'max:100', 'timezone'],
            'date_format' => ['sometimes', 'string', 'max:50'],
            'currency' => ['sometimes', 'string', 'max:10'],
            'grace_period_minutes' => ['sometimes', 'integer', 'min:0', 'max:120'],
            'allow_remote_checkin' => ['sometimes', 'boolean'],
            'leave_approval_levels' => ['sometimes', 'integer', 'min:1', 'max:5'],
            'probation_period_days' => ['sometimes', 'integer', 'min:0', 'max:365'],
        ];
    }
}

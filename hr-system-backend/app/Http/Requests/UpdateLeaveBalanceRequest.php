<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateLeaveBalanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Admin manual adjustment. total_days is the allotment; used_days
            // is exposed for correction flows (e.g. reconciling a bad import)
            // but it's optional.
            'total_days' => ['sometimes', 'numeric', 'min:0', 'max:365'],
            'used_days' => ['sometimes', 'numeric', 'min:0', 'max:365'],
        ];
    }
}

<?php

namespace App\Http\Requests;

use App\Enums\AssetCondition;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AssignAssetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'employee_id' => ['required', 'integer', 'exists:employees,id'],
            'condition_on_assign' => ['required', Rule::enum(AssetCondition::class)],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}

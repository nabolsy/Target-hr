<?php

namespace App\Http\Requests;

use App\Enums\AssetCondition;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReturnAssetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'condition_on_return' => ['required', Rule::enum(AssetCondition::class)],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}

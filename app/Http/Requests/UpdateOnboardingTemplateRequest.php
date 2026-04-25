<?php

namespace App\Http\Requests;

use App\Enums\OnboardingType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateOnboardingTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'type' => ['sometimes', 'required', Rule::enum(OnboardingType::class)],
            'department_id' => ['nullable', 'integer', 'exists:departments,id'],
            'is_default' => ['sometimes', 'boolean'],
            'items' => ['sometimes', 'array'],
            'items.*.title' => ['required_with:items', 'string', 'max:255'],
            'items.*.description' => ['nullable', 'string', 'max:1000'],
            'items.*.is_required' => ['sometimes', 'boolean'],
            'items.*.assigned_to_role' => ['nullable', 'string', 'max:255'],
            'items.*.sort_order' => ['sometimes', 'integer', 'min:0'],
        ];
    }
}

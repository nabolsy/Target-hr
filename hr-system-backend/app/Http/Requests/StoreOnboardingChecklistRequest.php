<?php

namespace App\Http\Requests;

use App\Enums\OnboardingType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreOnboardingChecklistRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'employee_id' => ['required', 'integer', 'exists:employees,id'],
            'template_id' => ['nullable', 'integer', 'exists:onboarding_templates,id'],
            'type' => ['required', Rule::enum(OnboardingType::class)],
        ];
    }
}

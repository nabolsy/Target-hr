<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SubmitReviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'metrics' => ['required', 'array', 'min:1'],
            'metrics.*.name' => ['required', 'string', 'max:255'],
            'metrics.*.weight' => ['required', 'numeric', 'min:0', 'max:100'],
            'metrics.*.score' => ['required', 'numeric', 'min:0', 'max:5'],
            'metrics.*.comments' => ['nullable', 'string', 'max:5000'],
            'manager_comments' => ['nullable', 'string', 'max:10000'],
            'goals_for_next_period' => ['nullable', 'string', 'max:10000'],
            'development_plan' => ['nullable', 'string', 'max:10000'],
            'promotion_recommendation' => ['nullable', 'boolean'],
        ];
    }
}

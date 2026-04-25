<?php

namespace App\Http\Requests;

use App\Enums\GoalStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateGoalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'target_value' => ['nullable', 'numeric', 'min:0'],
            'current_value' => ['nullable', 'numeric', 'min:0'],
            'unit' => ['nullable', 'string', 'max:50'],
            'status' => ['sometimes', Rule::enum(GoalStatus::class)],
            'due_date' => ['sometimes', 'date'],
            'review_cycle_id' => ['nullable', 'integer', 'exists:review_cycles,id'],
        ];
    }
}

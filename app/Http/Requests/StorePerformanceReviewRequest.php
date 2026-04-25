<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePerformanceReviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'review_cycle_id' => ['required', 'integer', 'exists:review_cycles,id'],
            'employee_id' => ['required', 'integer', 'exists:employees,id'],
            'reviewer_id' => ['required', 'integer', 'exists:users,id'],
            'type' => ['required', 'string', 'in:manager_review,self_review'],
        ];
    }
}

<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SubmitInterviewFeedbackRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'strengths' => ['nullable', 'string', 'max:5000'],
            'weaknesses' => ['nullable', 'string', 'max:5000'],
            'recommendation' => ['required', 'string', 'in:hire,reject,next_round,hold'],
            'comments' => ['nullable', 'string', 'max:10000'],
        ];
    }
}

<?php

namespace App\Http\Requests;

use App\Enums\InterviewType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ScheduleInterviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'candidate_id' => ['required', 'integer', 'exists:candidates,id'],
            'interviewer_id' => ['required', 'integer', 'exists:users,id'],
            'scheduled_at' => ['required', 'date', 'after:now'],
            'duration_minutes' => ['required', 'integer', 'min:15', 'max:480'],
            'type' => ['required', Rule::enum(InterviewType::class)],
            'location' => ['nullable', 'string', 'max:255'],
        ];
    }
}

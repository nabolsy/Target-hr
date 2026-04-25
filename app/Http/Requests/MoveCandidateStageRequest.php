<?php

namespace App\Http\Requests;

use App\Enums\RecruitmentStage;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MoveCandidateStageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'stage' => ['required', Rule::enum(RecruitmentStage::class)],
        ];
    }
}

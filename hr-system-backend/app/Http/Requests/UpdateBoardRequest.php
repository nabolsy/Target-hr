<?php

namespace App\Http\Requests;

use App\Enums\BoardType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class UpdateBoardRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'department_id' => ['nullable', 'integer', 'exists:departments,id'],
            'type' => ['sometimes', 'required', new Enum(BoardType::class)],
        ];
    }
}

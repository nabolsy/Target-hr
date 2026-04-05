<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MoveTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'column_id' => ['required', 'integer', 'exists:board_columns,id'],
            'sort_order' => ['required', 'integer', 'min:0'],
        ];
    }
}

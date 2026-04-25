<?php

namespace App\Http\Requests;

use App\Enums\DocumentType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'employee_id' => ['required', 'integer', 'exists:employees,id'],
            'title' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::enum(DocumentType::class)],
            'file' => ['required', 'file', 'max:10240'], // 10MB max
            'expiry_date' => ['nullable', 'date', 'after:today'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}

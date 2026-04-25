<?php

namespace App\Http\Requests;

use App\Enums\DocumentType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'type' => ['sometimes', 'required', Rule::enum(DocumentType::class)],
            'file' => ['sometimes', 'file', 'max:10240'], // 10MB max
            'expiry_date' => ['nullable', 'date', 'after:today'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}

<?php

namespace App\Http\Requests;

use App\Enums\AnnouncementType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAnnouncementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        // Multi-tenant default: an announcement is always scoped to
        // the author's company, so auto-fill it server-side.
        if (! $this->has('company_id') && $this->user()) {
            $this->merge(['company_id' => $this->user()->company_id]);
        }
    }

    public function rules(): array
    {
        return [
            'company_id' => ['sometimes', 'required', 'integer', 'exists:companies,id'],
            'department_id' => ['nullable', 'integer', 'exists:departments,id'],
            'title' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string', 'max:65535'],
            'type' => ['nullable', Rule::enum(AnnouncementType::class)],
            'is_pinned' => ['nullable', 'boolean'],
            'requires_acknowledgement' => ['nullable', 'boolean'],
            'published_at' => ['nullable', 'date'],
            'expires_at' => ['nullable', 'date', 'after:published_at'],
        ];
    }
}

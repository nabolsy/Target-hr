<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateLeaveTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'name_ar' => ['nullable', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
            'days_per_year' => ['sometimes', 'required', 'numeric', 'min:0', 'max:365'],
            'days_allowed' => ['sometimes', 'numeric', 'min:0', 'max:365'],
            'is_paid' => ['sometimes', 'boolean'],
            'requires_attachment' => ['sometimes', 'boolean'],
            'allows_half_day' => ['sometimes', 'boolean'],
            'is_active' => ['sometimes', 'boolean'],
            'description' => ['nullable', 'string', 'max:1000'],
            'color' => ['nullable', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('days_allowed') && ! $this->has('days_per_year')) {
            $this->merge(['days_per_year' => $this->input('days_allowed')]);
        }
    }

    public function validated($key = null, $default = null): array
    {
        $data = parent::validated($key, $default);
        unset($data['days_allowed']);

        return $data;
    }
}

<?php

namespace App\Http\Requests;

use App\Enums\PaymentFrequency;
use App\Enums\SalaryComponentType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSalaryStructureRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'employee_id' => ['required', 'integer', 'exists:employees,id'],
            'basic_salary' => ['required', 'numeric', 'min:0', 'max:9999999999.99'],
            'currency' => ['required', 'string', 'max:10'],
            'payment_frequency' => ['required', Rule::enum(PaymentFrequency::class)],
            'effective_date' => ['required', 'date'],
            'components' => ['nullable', 'array'],
            'components.*.name' => ['required_with:components', 'string', 'max:255'],
            'components.*.type' => ['required_with:components', Rule::enum(SalaryComponentType::class)],
            'components.*.amount' => ['required_with:components', 'numeric', 'min:0'],
            'components.*.is_percentage' => ['nullable', 'boolean'],
            'components.*.is_taxable' => ['nullable', 'boolean'],
        ];
    }
}

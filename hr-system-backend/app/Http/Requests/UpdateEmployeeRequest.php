<?php

namespace App\Http\Requests;

use App\Enums\EmployeeStatus;
use App\Enums\EmploymentType;
use App\Enums\Gender;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEmployeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $employeeId = $this->route('employee')?->id ?? $this->route('employee');

        return [
            'company_id' => ['nullable', 'integer', 'exists:companies,id'],
            'user_id' => ['nullable', 'integer', 'exists:users,id', Rule::unique('employees', 'user_id')->ignore($employeeId)],
            'department_id' => ['nullable', 'integer', 'exists:departments,id'],
            'designation_id' => ['nullable', 'integer', 'exists:designations,id'],
            'manager_id' => ['nullable', 'integer', 'exists:employees,id', "not_in:{$employeeId}"],
            'employee_id_number' => ['sometimes', 'required', 'string', 'max:50'],
            'first_name' => ['sometimes', 'required', 'string', 'max:255'],
            'last_name' => ['sometimes', 'required', 'string', 'max:255'],
            'email' => ['sometimes', 'required', 'email', 'max:255', Rule::unique('employees', 'email')->ignore($employeeId)],
            'phone' => ['nullable', 'string', 'max:50'],
            'date_of_birth' => ['nullable', 'date', 'before:today'],
            'gender' => ['nullable', Rule::enum(Gender::class)],
            'national_id' => ['nullable', 'string', 'max:100'],
            'address' => ['nullable', 'string', 'max:500'],
            'city' => ['nullable', 'string', 'max:100'],
            'state' => ['nullable', 'string', 'max:100'],
            'country' => ['nullable', 'string', 'max:100'],
            'postal_code' => ['nullable', 'string', 'max:20'],
            'profile_image' => ['nullable', 'string', 'max:255'],
            'employment_type' => ['sometimes', 'required', Rule::enum(EmploymentType::class)],
            'status' => ['nullable', Rule::enum(EmployeeStatus::class)],
            'join_date' => ['sometimes', 'required', 'date'],
            'probation_end_date' => ['nullable', 'date', 'after_or_equal:join_date'],
            'work_location' => ['nullable', 'string', 'max:255'],
            'salary' => ['nullable', 'numeric', 'min:0', 'max:9999999999.99'],
            'bank_name' => ['nullable', 'string', 'max:255'],
            'bank_account_number' => ['nullable', 'string', 'max:100'],
            'emergency_contact_name' => ['nullable', 'string', 'max:255'],
            'emergency_contact_phone' => ['nullable', 'string', 'max:50'],
            'emergency_contact_relation' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }
}

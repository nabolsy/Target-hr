<?php

namespace App\DTOs;

use App\Enums\EmployeeStatus;
use App\Enums\EmploymentType;
use App\Enums\Gender;

readonly class EmployeeDTO
{
    public function __construct(
        public ?int $companyId = null,
        public ?int $userId = null,
        public ?int $departmentId = null,
        public ?int $designationId = null,
        public ?int $managerId = null,
        public ?string $employeeIdNumber = null,
        public ?string $firstName = null,
        public ?string $lastName = null,
        public ?string $email = null,
        public ?string $phone = null,
        public ?string $dateOfBirth = null,
        public ?Gender $gender = null,
        public ?string $nationalId = null,
        public ?string $address = null,
        public ?string $city = null,
        public ?string $state = null,
        public ?string $country = null,
        public ?string $postalCode = null,
        public ?string $profileImage = null,
        public ?EmploymentType $employmentType = null,
        public ?EmployeeStatus $status = null,
        public ?string $joinDate = null,
        public ?string $probationEndDate = null,
        public ?string $workLocation = null,
        public ?string $salary = null,
        public ?string $bankName = null,
        public ?string $bankAccountNumber = null,
        public ?string $emergencyContactName = null,
        public ?string $emergencyContactPhone = null,
        public ?string $emergencyContactRelation = null,
        public ?string $notes = null,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            companyId: $data['company_id'] ?? null,
            userId: $data['user_id'] ?? null,
            departmentId: $data['department_id'] ?? null,
            designationId: $data['designation_id'] ?? null,
            managerId: $data['manager_id'] ?? null,
            employeeIdNumber: $data['employee_id_number'] ?? null,
            firstName: $data['first_name'] ?? null,
            lastName: $data['last_name'] ?? null,
            email: $data['email'] ?? null,
            phone: $data['phone'] ?? null,
            dateOfBirth: $data['date_of_birth'] ?? null,
            gender: isset($data['gender']) ? Gender::from($data['gender']) : null,
            nationalId: $data['national_id'] ?? null,
            address: $data['address'] ?? null,
            city: $data['city'] ?? null,
            state: $data['state'] ?? null,
            country: $data['country'] ?? null,
            postalCode: $data['postal_code'] ?? null,
            profileImage: $data['profile_image'] ?? null,
            employmentType: isset($data['employment_type']) ? EmploymentType::from($data['employment_type']) : null,
            status: isset($data['status']) ? EmployeeStatus::from($data['status']) : null,
            joinDate: $data['join_date'] ?? null,
            probationEndDate: $data['probation_end_date'] ?? null,
            workLocation: $data['work_location'] ?? null,
            salary: $data['salary'] ?? null,
            bankName: $data['bank_name'] ?? null,
            bankAccountNumber: $data['bank_account_number'] ?? null,
            emergencyContactName: $data['emergency_contact_name'] ?? null,
            emergencyContactPhone: $data['emergency_contact_phone'] ?? null,
            emergencyContactRelation: $data['emergency_contact_relation'] ?? null,
            notes: $data['notes'] ?? null,
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'company_id' => $this->companyId,
            'user_id' => $this->userId,
            'department_id' => $this->departmentId,
            'designation_id' => $this->designationId,
            'manager_id' => $this->managerId,
            'employee_id_number' => $this->employeeIdNumber,
            'first_name' => $this->firstName,
            'last_name' => $this->lastName,
            'email' => $this->email,
            'phone' => $this->phone,
            'date_of_birth' => $this->dateOfBirth,
            'gender' => $this->gender?->value,
            'national_id' => $this->nationalId,
            'address' => $this->address,
            'city' => $this->city,
            'state' => $this->state,
            'country' => $this->country,
            'postal_code' => $this->postalCode,
            'profile_image' => $this->profileImage,
            'employment_type' => $this->employmentType?->value,
            'status' => $this->status?->value,
            'join_date' => $this->joinDate,
            'probation_end_date' => $this->probationEndDate,
            'work_location' => $this->workLocation,
            'salary' => $this->salary,
            'bank_name' => $this->bankName,
            'bank_account_number' => $this->bankAccountNumber,
            'emergency_contact_name' => $this->emergencyContactName,
            'emergency_contact_phone' => $this->emergencyContactPhone,
            'emergency_contact_relation' => $this->emergencyContactRelation,
            'notes' => $this->notes,
        ], fn ($value) => $value !== null);
    }
}

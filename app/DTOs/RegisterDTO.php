<?php

namespace App\DTOs;

readonly class RegisterDTO
{
    public function __construct(
        public string $companyName,
        public string $name,
        public string $email,
        public string $password,
        public ?string $companyEmail = null,
        public ?string $phone = null,
        public ?string $address = null,
        public ?string $city = null,
        public ?string $state = null,
        public ?string $country = null,
        public ?string $postalCode = null,
        public ?string $website = null,
        public ?string $industry = null,
        public ?int $employeeLimit = null,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            companyName: $data['company_name'],
            name: $data['name'],
            email: $data['email'],
            password: $data['password'],
            companyEmail: $data['company_email'] ?? null,
            phone: $data['phone'] ?? null,
            address: $data['address'] ?? null,
            city: $data['city'] ?? null,
            state: $data['state'] ?? null,
            country: $data['country'] ?? null,
            postalCode: $data['postal_code'] ?? null,
            website: $data['website'] ?? null,
            industry: $data['industry'] ?? null,
            employeeLimit: $data['employee_limit'] ?? null,
        );
    }
}

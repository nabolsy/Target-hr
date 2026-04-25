<?php

namespace App\DTOs;

readonly class CompanyBranchDTO
{
    public function __construct(
        public ?int $companyId = null,
        public ?string $name = null,
        public ?string $nameAr = null,
        public ?string $address = null,
        public ?string $city = null,
        public ?string $state = null,
        public ?string $country = null,
        public ?string $postalCode = null,
        public ?string $phone = null,
        public ?string $email = null,
        public ?int $managerId = null,
        public ?bool $isHeadquarters = null,
        public ?bool $isActive = null,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            companyId: $data['company_id'] ?? null,
            name: $data['name'] ?? null,
            nameAr: $data['name_ar'] ?? null,
            address: $data['address'] ?? null,
            city: $data['city'] ?? null,
            state: $data['state'] ?? null,
            country: $data['country'] ?? null,
            postalCode: $data['postal_code'] ?? null,
            phone: $data['phone'] ?? null,
            email: $data['email'] ?? null,
            // array_key_exists so a caller can explicitly clear the
            // manager by sending null.
            managerId: array_key_exists('manager_id', $data) ? $data['manager_id'] : null,
            isHeadquarters: isset($data['is_headquarters']) ? (bool) $data['is_headquarters'] : null,
            isActive: isset($data['is_active']) ? (bool) $data['is_active'] : null,
        );
    }

    public function toArray(): array
    {
        $out = [
            'company_id' => $this->companyId,
            'name' => $this->name,
            'name_ar' => $this->nameAr,
            'address' => $this->address,
            'city' => $this->city,
            'state' => $this->state,
            'country' => $this->country,
            'postal_code' => $this->postalCode,
            'phone' => $this->phone,
            'email' => $this->email,
            'manager_id' => $this->managerId,
            'is_headquarters' => $this->isHeadquarters,
            'is_active' => $this->isActive,
        ];

        return array_filter($out, fn ($value) => $value !== null);
    }
}

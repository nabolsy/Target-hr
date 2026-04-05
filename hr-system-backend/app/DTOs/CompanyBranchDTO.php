<?php

namespace App\DTOs;

readonly class CompanyBranchDTO
{
    public function __construct(
        public ?int $companyId = null,
        public ?string $name = null,
        public ?string $address = null,
        public ?string $city = null,
        public ?string $state = null,
        public ?string $country = null,
        public ?string $postalCode = null,
        public ?string $phone = null,
        public ?string $email = null,
        public ?bool $isHeadquarters = null,
        public ?bool $isActive = null,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            companyId: $data['company_id'] ?? null,
            name: $data['name'] ?? null,
            address: $data['address'] ?? null,
            city: $data['city'] ?? null,
            state: $data['state'] ?? null,
            country: $data['country'] ?? null,
            postalCode: $data['postal_code'] ?? null,
            phone: $data['phone'] ?? null,
            email: $data['email'] ?? null,
            isHeadquarters: isset($data['is_headquarters']) ? (bool) $data['is_headquarters'] : null,
            isActive: isset($data['is_active']) ? (bool) $data['is_active'] : null,
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'company_id' => $this->companyId,
            'name' => $this->name,
            'address' => $this->address,
            'city' => $this->city,
            'state' => $this->state,
            'country' => $this->country,
            'postal_code' => $this->postalCode,
            'phone' => $this->phone,
            'email' => $this->email,
            'is_headquarters' => $this->isHeadquarters,
            'is_active' => $this->isActive,
        ], fn ($value) => $value !== null);
    }
}

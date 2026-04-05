<?php

namespace App\DTOs;

use App\Enums\CompanyStatus;
use App\Enums\SubscriptionPlan;

readonly class CompanyDTO
{
    public function __construct(
        public string $name,
        public string $email,
        public ?string $phone = null,
        public ?string $address = null,
        public ?string $city = null,
        public ?string $state = null,
        public ?string $country = null,
        public ?string $postalCode = null,
        public ?string $website = null,
        public ?string $logo = null,
        public ?string $industry = null,
        public ?int $employeeLimit = null,
        public ?CompanyStatus $status = null,
        public ?SubscriptionPlan $subscriptionPlan = null,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'],
            email: $data['email'],
            phone: $data['phone'] ?? null,
            address: $data['address'] ?? null,
            city: $data['city'] ?? null,
            state: $data['state'] ?? null,
            country: $data['country'] ?? null,
            postalCode: $data['postal_code'] ?? null,
            website: $data['website'] ?? null,
            logo: $data['logo'] ?? null,
            industry: $data['industry'] ?? null,
            employeeLimit: $data['employee_limit'] ?? null,
            status: isset($data['status']) ? CompanyStatus::from($data['status']) : null,
            subscriptionPlan: isset($data['subscription_plan']) ? SubscriptionPlan::from($data['subscription_plan']) : null,
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'address' => $this->address,
            'city' => $this->city,
            'state' => $this->state,
            'country' => $this->country,
            'postal_code' => $this->postalCode,
            'website' => $this->website,
            'logo' => $this->logo,
            'industry' => $this->industry,
            'employee_limit' => $this->employeeLimit,
            'status' => $this->status?->value,
            'subscription_plan' => $this->subscriptionPlan?->value,
        ], fn ($value) => $value !== null);
    }
}

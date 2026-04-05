<?php

namespace App\DTOs;

use App\Enums\OnboardingType;

readonly class OnboardingTemplateDTO
{
    public function __construct(
        public ?int $companyId = null,
        public ?int $departmentId = null,
        public ?string $name = null,
        public ?OnboardingType $type = null,
        public ?bool $isDefault = null,
        public ?array $items = null,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            companyId: $data['company_id'] ?? null,
            departmentId: $data['department_id'] ?? null,
            name: $data['name'] ?? null,
            type: isset($data['type']) ? OnboardingType::from($data['type']) : null,
            isDefault: $data['is_default'] ?? null,
            items: $data['items'] ?? null,
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'company_id' => $this->companyId,
            'department_id' => $this->departmentId,
            'name' => $this->name,
            'type' => $this->type?->value,
            'is_default' => $this->isDefault,
        ], fn ($value) => $value !== null);
    }
}

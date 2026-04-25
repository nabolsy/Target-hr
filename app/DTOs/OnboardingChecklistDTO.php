<?php

namespace App\DTOs;

use App\Enums\OnboardingType;

readonly class OnboardingChecklistDTO
{
    public function __construct(
        public ?int $companyId = null,
        public ?int $employeeId = null,
        public ?int $templateId = null,
        public ?OnboardingType $type = null,
        public ?int $createdBy = null,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            companyId: $data['company_id'] ?? null,
            employeeId: $data['employee_id'] ?? null,
            templateId: $data['template_id'] ?? null,
            type: isset($data['type']) ? OnboardingType::from($data['type']) : null,
            createdBy: $data['created_by'] ?? null,
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'company_id' => $this->companyId,
            'employee_id' => $this->employeeId,
            'template_id' => $this->templateId,
            'type' => $this->type?->value,
            'created_by' => $this->createdBy,
        ], fn ($value) => $value !== null);
    }
}

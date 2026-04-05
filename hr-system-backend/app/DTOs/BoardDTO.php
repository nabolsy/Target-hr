<?php

namespace App\DTOs;

use App\Enums\BoardType;

readonly class BoardDTO
{
    public function __construct(
        public ?int $companyId = null,
        public ?string $name = null,
        public ?string $description = null,
        public ?int $departmentId = null,
        public ?BoardType $type = null,
        public ?bool $isArchived = null,
        public ?int $createdBy = null,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            companyId: $data['company_id'] ?? null,
            name: $data['name'] ?? null,
            description: $data['description'] ?? null,
            departmentId: $data['department_id'] ?? null,
            type: isset($data['type']) ? BoardType::from($data['type']) : null,
            isArchived: $data['is_archived'] ?? null,
            createdBy: $data['created_by'] ?? null,
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'company_id' => $this->companyId,
            'name' => $this->name,
            'description' => $this->description,
            'department_id' => $this->departmentId,
            'type' => $this->type?->value,
            'is_archived' => $this->isArchived,
            'created_by' => $this->createdBy,
        ], fn ($value) => $value !== null);
    }
}

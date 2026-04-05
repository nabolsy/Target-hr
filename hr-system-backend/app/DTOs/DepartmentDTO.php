<?php

namespace App\DTOs;

use App\Enums\DepartmentStatus;

readonly class DepartmentDTO
{
    public function __construct(
        public string $name,
        public int $companyId,
        public ?int $parentId = null,
        public ?string $description = null,
        public ?int $managerId = null,
        public ?DepartmentStatus $status = null,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'],
            companyId: $data['company_id'],
            parentId: $data['parent_id'] ?? null,
            description: $data['description'] ?? null,
            managerId: $data['manager_id'] ?? null,
            status: isset($data['status']) ? DepartmentStatus::from($data['status']) : null,
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'name' => $this->name,
            'company_id' => $this->companyId,
            'parent_id' => $this->parentId,
            'description' => $this->description,
            'manager_id' => $this->managerId,
            'status' => $this->status?->value,
        ], fn ($value) => $value !== null);
    }
}

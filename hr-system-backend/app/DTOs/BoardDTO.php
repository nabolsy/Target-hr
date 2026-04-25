<?php

namespace App\DTOs;

use App\Enums\BoardType;

readonly class BoardDTO
{
    public function __construct(
        public ?int $companyId = null,
        public ?string $name = null,
        public ?string $description = null,
        public ?string $color = null,
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
            color: $data['color'] ?? null,
            departmentId: array_key_exists('department_id', $data) ? $data['department_id'] : null,
            type: isset($data['type']) ? BoardType::from($data['type']) : null,
            isArchived: $data['is_archived'] ?? null,
            createdBy: $data['created_by'] ?? null,
        );
    }

    public function toArray(): array
    {
        // department_id is kept explicitly so callers can blank it out
        // (null = company-wide). array_filter drops nulls, which would
        // prevent "make this board company-wide" from working, so we
        // build the array manually and only drop true nulls for fields
        // other than department_id when it was explicitly set.
        $out = [
            'company_id' => $this->companyId,
            'name' => $this->name,
            'description' => $this->description,
            'color' => $this->color,
            'department_id' => $this->departmentId,
            'type' => $this->type?->value,
            'is_archived' => $this->isArchived,
            'created_by' => $this->createdBy,
        ];

        return array_filter($out, fn ($value) => $value !== null);
    }
}

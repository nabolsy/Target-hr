<?php

namespace App\DTOs;

readonly class DesignationDTO
{
    public function __construct(
        public string $name,
        public int $companyId,
        public ?string $description = null,
        public ?int $level = null,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'],
            companyId: $data['company_id'],
            description: $data['description'] ?? null,
            level: $data['level'] ?? null,
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'name' => $this->name,
            'company_id' => $this->companyId,
            'description' => $this->description,
            'level' => $this->level,
        ], fn ($value) => $value !== null);
    }
}

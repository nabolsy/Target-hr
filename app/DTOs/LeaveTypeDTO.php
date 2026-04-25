<?php

namespace App\DTOs;

readonly class LeaveTypeDTO
{
    public function __construct(
        public string $name,
        public string $slug,
        public float $daysPerYear,
        public bool $isPaid = true,
        public bool $requiresAttachment = false,
        public bool $isActive = true,
        public ?string $description = null,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'],
            slug: $data['slug'] ?? \Illuminate\Support\Str::slug($data['name']),
            daysPerYear: (float) $data['days_per_year'],
            isPaid: $data['is_paid'] ?? true,
            requiresAttachment: $data['requires_attachment'] ?? false,
            isActive: $data['is_active'] ?? true,
            description: $data['description'] ?? null,
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'name' => $this->name,
            'slug' => $this->slug,
            'days_per_year' => $this->daysPerYear,
            'is_paid' => $this->isPaid,
            'requires_attachment' => $this->requiresAttachment,
            'is_active' => $this->isActive,
            'description' => $this->description,
        ], fn ($value) => $value !== null);
    }
}

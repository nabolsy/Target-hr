<?php

namespace App\DTOs;

use App\Enums\AnnouncementType;

readonly class AnnouncementDTO
{
    public function __construct(
        public string $title,
        public string $body,
        public int $companyId,
        public ?int $departmentId = null,
        public ?AnnouncementType $type = null,
        public ?bool $isPinned = null,
        public ?bool $requiresAcknowledgement = null,
        public ?string $publishedAt = null,
        public ?string $expiresAt = null,
        public ?int $createdBy = null,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            title: $data['title'],
            body: $data['body'],
            companyId: $data['company_id'],
            departmentId: $data['department_id'] ?? null,
            type: isset($data['type']) ? AnnouncementType::from($data['type']) : null,
            isPinned: $data['is_pinned'] ?? null,
            requiresAcknowledgement: $data['requires_acknowledgement'] ?? null,
            publishedAt: $data['published_at'] ?? null,
            expiresAt: $data['expires_at'] ?? null,
            createdBy: $data['created_by'] ?? null,
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'title' => $this->title,
            'body' => $this->body,
            'company_id' => $this->companyId,
            'department_id' => $this->departmentId,
            'type' => $this->type?->value,
            'is_pinned' => $this->isPinned,
            'requires_acknowledgement' => $this->requiresAcknowledgement,
            'published_at' => $this->publishedAt,
            'expires_at' => $this->expiresAt,
            'created_by' => $this->createdBy,
        ], fn ($value) => $value !== null);
    }
}

<?php

namespace App\DTOs;

use App\Enums\TaskPriority;

readonly class TaskDTO
{
    public function __construct(
        public ?int $companyId = null,
        public ?int $boardId = null,
        public ?int $columnId = null,
        public ?string $title = null,
        public ?string $description = null,
        public ?int $creatorId = null,
        public ?TaskPriority $priority = null,
        public ?string $startDate = null,
        public ?string $dueDate = null,
        public ?string $estimatedHours = null,
        public ?string $actualHours = null,
        public ?int $completionPercentage = null,
        public ?int $sortOrder = null,
        public ?bool $isArchived = null,
        public ?array $assigneeIds = null,
        public ?array $labelIds = null,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            companyId: $data['company_id'] ?? null,
            boardId: $data['board_id'] ?? null,
            columnId: $data['column_id'] ?? null,
            title: $data['title'] ?? null,
            description: $data['description'] ?? null,
            creatorId: $data['creator_id'] ?? null,
            priority: isset($data['priority']) ? TaskPriority::from($data['priority']) : null,
            startDate: $data['start_date'] ?? null,
            dueDate: $data['due_date'] ?? null,
            estimatedHours: $data['estimated_hours'] ?? null,
            actualHours: $data['actual_hours'] ?? null,
            completionPercentage: $data['completion_percentage'] ?? null,
            sortOrder: $data['sort_order'] ?? null,
            isArchived: $data['is_archived'] ?? null,
            assigneeIds: $data['assignee_ids'] ?? null,
            labelIds: $data['label_ids'] ?? null,
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'company_id' => $this->companyId,
            'board_id' => $this->boardId,
            'column_id' => $this->columnId,
            'title' => $this->title,
            'description' => $this->description,
            'creator_id' => $this->creatorId,
            'priority' => $this->priority?->value,
            'start_date' => $this->startDate,
            'due_date' => $this->dueDate,
            'estimated_hours' => $this->estimatedHours,
            'actual_hours' => $this->actualHours,
            'completion_percentage' => $this->completionPercentage,
            'sort_order' => $this->sortOrder,
            'is_archived' => $this->isArchived,
        ], fn ($value) => $value !== null);
    }
}

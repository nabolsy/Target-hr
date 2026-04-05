<?php

namespace App\DTOs;

readonly class GoalDTO
{
    public function __construct(
        public ?int $companyId = null,
        public ?int $employeeId = null,
        public ?int $reviewCycleId = null,
        public ?string $title = null,
        public ?string $description = null,
        public ?string $targetValue = null,
        public ?string $currentValue = null,
        public ?string $unit = null,
        public ?string $status = null,
        public ?string $dueDate = null,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            companyId: $data['company_id'] ?? null,
            employeeId: $data['employee_id'] ?? null,
            reviewCycleId: $data['review_cycle_id'] ?? null,
            title: $data['title'] ?? null,
            description: $data['description'] ?? null,
            targetValue: $data['target_value'] ?? null,
            currentValue: $data['current_value'] ?? null,
            unit: $data['unit'] ?? null,
            status: $data['status'] ?? null,
            dueDate: $data['due_date'] ?? null,
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'company_id' => $this->companyId,
            'employee_id' => $this->employeeId,
            'review_cycle_id' => $this->reviewCycleId,
            'title' => $this->title,
            'description' => $this->description,
            'target_value' => $this->targetValue,
            'current_value' => $this->currentValue,
            'unit' => $this->unit,
            'status' => $this->status,
            'due_date' => $this->dueDate,
        ], fn ($value) => $value !== null);
    }
}

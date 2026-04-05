<?php

namespace App\DTOs;

readonly class ShiftDTO
{
    public function __construct(
        public int $companyId,
        public string $name,
        public string $startTime,
        public string $endTime,
        public int $breakDurationMinutes = 0,
        public bool $isDefault = false,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            companyId: $data['company_id'],
            name: $data['name'],
            startTime: $data['start_time'],
            endTime: $data['end_time'],
            breakDurationMinutes: $data['break_duration_minutes'] ?? 0,
            isDefault: $data['is_default'] ?? false,
        );
    }

    public function toArray(): array
    {
        return [
            'company_id' => $this->companyId,
            'name' => $this->name,
            'start_time' => $this->startTime,
            'end_time' => $this->endTime,
            'break_duration_minutes' => $this->breakDurationMinutes,
            'is_default' => $this->isDefault,
        ];
    }
}

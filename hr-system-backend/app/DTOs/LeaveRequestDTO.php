<?php

namespace App\DTOs;

readonly class LeaveRequestDTO
{
    public function __construct(
        public int $employeeId,
        public int $leaveTypeId,
        public string $startDate,
        public string $endDate,
        public bool $isHalfDay = false,
        public string $reason = '',
        public ?string $attachmentPath = null,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            employeeId: $data['employee_id'],
            leaveTypeId: $data['leave_type_id'],
            startDate: $data['start_date'],
            endDate: $data['end_date'],
            isHalfDay: $data['is_half_day'] ?? false,
            reason: $data['reason'] ?? '',
            attachmentPath: $data['attachment_path'] ?? null,
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'employee_id' => $this->employeeId,
            'leave_type_id' => $this->leaveTypeId,
            'start_date' => $this->startDate,
            'end_date' => $this->endDate,
            'is_half_day' => $this->isHalfDay,
            'reason' => $this->reason,
            'attachment_path' => $this->attachmentPath,
        ], fn ($value) => $value !== null);
    }
}

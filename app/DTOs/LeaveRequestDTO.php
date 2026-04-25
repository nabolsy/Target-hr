<?php

namespace App\DTOs;

readonly class LeaveRequestDTO
{
    // Canonical duration type values. Any half-day variant yields
    // total_days = 0.5 in LeaveService::applyForLeave and forces
    // end_date = start_date.
    public const DURATION_FULL        = 'full';
    public const DURATION_FIRST_HALF  = 'first_half';
    public const DURATION_SECOND_HALF = 'second_half';

    public function __construct(
        public int $employeeId,
        public int $leaveTypeId,
        public string $startDate,
        public string $endDate,
        public bool $isHalfDay = false,
        public string $durationType = self::DURATION_FULL,
        public string $reason = '',
        public ?string $attachmentPath = null,
    ) {
    }

    public static function fromArray(array $data): self
    {
        // Normalize: if `duration_type` is set we trust it; otherwise
        // fall back to the legacy `is_half_day` boolean (first half).
        $durationType = $data['duration_type'] ?? null;
        $isHalfDay = $data['is_half_day'] ?? false;

        if ($durationType === null) {
            $durationType = $isHalfDay ? self::DURATION_FIRST_HALF : self::DURATION_FULL;
        }
        if (! in_array($durationType, [self::DURATION_FULL, self::DURATION_FIRST_HALF, self::DURATION_SECOND_HALF], true)) {
            $durationType = self::DURATION_FULL;
        }
        $isHalfDay = $durationType !== self::DURATION_FULL;

        return new self(
            employeeId: $data['employee_id'],
            leaveTypeId: $data['leave_type_id'],
            startDate: $data['start_date'],
            endDate: $data['end_date'],
            isHalfDay: $isHalfDay,
            durationType: $durationType,
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
            'duration_type' => $this->durationType,
            'reason' => $this->reason,
            'attachment_path' => $this->attachmentPath,
        ], fn ($value) => $value !== null);
    }
}

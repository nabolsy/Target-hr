<?php

namespace App\DTOs;

readonly class CompanySettingDTO
{
    public function __construct(
        public ?string $workStartTime = null,
        public ?string $workEndTime = null,
        public ?array $workDays = null,
        public ?string $timezone = null,
        public ?string $dateFormat = null,
        public ?string $currency = null,
        public ?int $gracePeriodMinutes = null,
        public ?bool $allowRemoteCheckin = null,
        public ?int $leaveApprovalLevels = null,
        public ?int $probationPeriodDays = null,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            workStartTime: $data['work_start_time'] ?? null,
            workEndTime: $data['work_end_time'] ?? null,
            workDays: $data['work_days'] ?? null,
            timezone: $data['timezone'] ?? null,
            dateFormat: $data['date_format'] ?? null,
            currency: $data['currency'] ?? null,
            gracePeriodMinutes: isset($data['grace_period_minutes']) ? (int) $data['grace_period_minutes'] : null,
            allowRemoteCheckin: isset($data['allow_remote_checkin']) ? (bool) $data['allow_remote_checkin'] : null,
            leaveApprovalLevels: isset($data['leave_approval_levels']) ? (int) $data['leave_approval_levels'] : null,
            probationPeriodDays: isset($data['probation_period_days']) ? (int) $data['probation_period_days'] : null,
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'work_start_time' => $this->workStartTime,
            'work_end_time' => $this->workEndTime,
            'work_days' => $this->workDays,
            'timezone' => $this->timezone,
            'date_format' => $this->dateFormat,
            'currency' => $this->currency,
            'grace_period_minutes' => $this->gracePeriodMinutes,
            'allow_remote_checkin' => $this->allowRemoteCheckin,
            'leave_approval_levels' => $this->leaveApprovalLevels,
            'probation_period_days' => $this->probationPeriodDays,
        ], fn ($value) => $value !== null);
    }
}

<?php

namespace App\DTOs;

use Carbon\Carbon;

readonly class AttendanceDTO
{
    public function __construct(
        public int $companyId,
        public int $employeeId,
        public Carbon $date,
        public ?Carbon $checkIn = null,
        public ?Carbon $checkOut = null,
        public ?int $shiftId = null,
        public ?string $notes = null,
        public ?string $ipAddress = null,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            companyId: $data['company_id'],
            employeeId: $data['employee_id'],
            date: $data['date'] instanceof Carbon ? $data['date'] : Carbon::parse($data['date']),
            checkIn: isset($data['check_in']) ? Carbon::parse($data['check_in']) : null,
            checkOut: isset($data['check_out']) ? Carbon::parse($data['check_out']) : null,
            shiftId: $data['shift_id'] ?? null,
            notes: $data['notes'] ?? null,
            ipAddress: $data['ip_address'] ?? null,
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'company_id' => $this->companyId,
            'employee_id' => $this->employeeId,
            'date' => $this->date->toDateString(),
            'check_in' => $this->checkIn?->toDateTimeString(),
            'check_out' => $this->checkOut?->toDateTimeString(),
            'shift_id' => $this->shiftId,
            'notes' => $this->notes,
            'ip_address' => $this->ipAddress,
        ], fn ($value) => $value !== null);
    }
}

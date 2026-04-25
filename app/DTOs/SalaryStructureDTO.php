<?php

namespace App\DTOs;

use App\Enums\PaymentFrequency;

readonly class SalaryStructureDTO
{
    public function __construct(
        public ?int $companyId = null,
        public ?int $employeeId = null,
        public ?string $basicSalary = null,
        public ?string $currency = null,
        public ?PaymentFrequency $paymentFrequency = null,
        public ?string $effectiveDate = null,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            companyId: $data['company_id'] ?? null,
            employeeId: $data['employee_id'] ?? null,
            basicSalary: $data['basic_salary'] ?? null,
            currency: $data['currency'] ?? null,
            paymentFrequency: isset($data['payment_frequency']) ? PaymentFrequency::from($data['payment_frequency']) : null,
            effectiveDate: $data['effective_date'] ?? null,
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'company_id' => $this->companyId,
            'employee_id' => $this->employeeId,
            'basic_salary' => $this->basicSalary,
            'currency' => $this->currency,
            'payment_frequency' => $this->paymentFrequency?->value,
            'effective_date' => $this->effectiveDate,
        ], fn ($value) => $value !== null);
    }
}

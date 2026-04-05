<?php

namespace App\Enums;

enum SalaryComponentType: string
{
    case Allowance = 'allowance';
    case Deduction = 'deduction';

    public function label(): string
    {
        return match ($this) {
            self::Allowance => 'Allowance',
            self::Deduction => 'Deduction',
        };
    }
}

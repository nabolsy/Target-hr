<?php

namespace App\Enums;

enum PaymentFrequency: string
{
    case Monthly = 'monthly';
    case BiWeekly = 'bi_weekly';
    case Weekly = 'weekly';

    public function label(): string
    {
        return match ($this) {
            self::Monthly => 'Monthly',
            self::BiWeekly => 'Bi-Weekly',
            self::Weekly => 'Weekly',
        };
    }
}

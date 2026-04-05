<?php

namespace App\Enums;

enum ReviewType: string
{
    case Monthly = 'monthly';
    case Quarterly = 'quarterly';
    case Annual = 'annual';
    case Probation = 'probation';
    case Custom = 'custom';

    public function label(): string
    {
        return match ($this) {
            self::Monthly => 'Monthly',
            self::Quarterly => 'Quarterly',
            self::Annual => 'Annual',
            self::Probation => 'Probation',
            self::Custom => 'Custom',
        };
    }
}

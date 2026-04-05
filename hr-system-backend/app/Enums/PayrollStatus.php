<?php

namespace App\Enums;

enum PayrollStatus: string
{
    case Draft = 'draft';
    case Generated = 'generated';
    case Locked = 'locked';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Generated => 'Generated',
            self::Locked => 'Locked',
        };
    }
}

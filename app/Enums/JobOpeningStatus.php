<?php

namespace App\Enums;

enum JobOpeningStatus: string
{
    case Draft = 'draft';
    case Open = 'open';
    case Closed = 'closed';
    case OnHold = 'on_hold';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Open => 'Open',
            self::Closed => 'Closed',
            self::OnHold => 'On Hold',
        };
    }
}

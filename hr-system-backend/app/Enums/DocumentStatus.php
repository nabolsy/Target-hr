<?php

namespace App\Enums;

enum DocumentStatus: string
{
    case Active = 'active';
    case Expiring = 'expiring';
    case Expired = 'expired';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Active',
            self::Expiring => 'Expiring',
            self::Expired => 'Expired',
        };
    }
}

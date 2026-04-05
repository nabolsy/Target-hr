<?php

namespace App\Enums;

enum AssetCategory: string
{
    case Laptop = 'laptop';
    case Phone = 'phone';
    case IdCard = 'id_card';
    case SimCard = 'sim_card';
    case AccessCard = 'access_card';
    case Monitor = 'monitor';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Laptop => 'Laptop',
            self::Phone => 'Phone',
            self::IdCard => 'ID Card',
            self::SimCard => 'SIM Card',
            self::AccessCard => 'Access Card',
            self::Monitor => 'Monitor',
            self::Other => 'Other',
        };
    }
}

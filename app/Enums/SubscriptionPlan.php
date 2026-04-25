<?php

namespace App\Enums;

enum SubscriptionPlan: string
{
    case Free = 'free';
    case Basic = 'basic';
    case Professional = 'professional';
    case Enterprise = 'enterprise';

    public function label(): string
    {
        return match ($this) {
            self::Free => 'Free',
            self::Basic => 'Basic',
            self::Professional => 'Professional',
            self::Enterprise => 'Enterprise',
        };
    }

    public function maxEmployees(): int
    {
        return match ($this) {
            self::Free => 10,
            self::Basic => 50,
            self::Professional => 200,
            self::Enterprise => PHP_INT_MAX,
        };
    }
}

<?php

namespace App\Enums;

enum OnboardingType: string
{
    case Onboarding = 'onboarding';
    case Offboarding = 'offboarding';

    public function label(): string
    {
        return match ($this) {
            self::Onboarding => 'Onboarding',
            self::Offboarding => 'Offboarding',
        };
    }
}

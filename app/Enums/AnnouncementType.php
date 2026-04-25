<?php

namespace App\Enums;

enum AnnouncementType: string
{
    case General = 'general';
    case Policy = 'policy';
    case Event = 'event';
    case Urgent = 'urgent';

    public function label(): string
    {
        return match ($this) {
            self::General => 'General',
            self::Policy => 'Policy',
            self::Event => 'Event',
            self::Urgent => 'Urgent',
        };
    }
}

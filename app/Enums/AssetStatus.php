<?php

namespace App\Enums;

enum AssetStatus: string
{
    case Available = 'available';
    case Assigned = 'assigned';
    case Maintenance = 'maintenance';
    case Disposed = 'disposed';

    public function label(): string
    {
        return match ($this) {
            self::Available => 'Available',
            self::Assigned => 'Assigned',
            self::Maintenance => 'Maintenance',
            self::Disposed => 'Disposed',
        };
    }
}

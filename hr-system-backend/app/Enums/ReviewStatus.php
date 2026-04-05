<?php

namespace App\Enums;

enum ReviewStatus: string
{
    // Cycle statuses
    case Draft = 'draft';
    case Active = 'active';
    case Completed = 'completed';

    // Review statuses
    case Pending = 'pending';
    case InProgress = 'in_progress';
    case Submitted = 'submitted';
    case Acknowledged = 'acknowledged';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Active => 'Active',
            self::Completed => 'Completed',
            self::Pending => 'Pending',
            self::InProgress => 'In Progress',
            self::Submitted => 'Submitted',
            self::Acknowledged => 'Acknowledged',
        };
    }

    /**
     * Get only statuses valid for review cycles.
     */
    public static function cycleStatuses(): array
    {
        return [
            self::Draft,
            self::Active,
            self::Completed,
        ];
    }

    /**
     * Get only statuses valid for individual reviews.
     */
    public static function reviewStatuses(): array
    {
        return [
            self::Pending,
            self::InProgress,
            self::Submitted,
            self::Acknowledged,
        ];
    }
}

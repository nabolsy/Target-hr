<?php

namespace App\Enums;

enum EmployeeStatus: string
{
    case Active = 'active';
    case Inactive = 'inactive';
    case Probation = 'probation';
    case Resigned = 'resigned';
    case Terminated = 'terminated';
    case OnLeave = 'on_leave';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Active',
            self::Inactive => 'Inactive',
            self::Probation => 'Probation',
            self::Resigned => 'Resigned',
            self::Terminated => 'Terminated',
            self::OnLeave => 'On Leave',
        };
    }
}

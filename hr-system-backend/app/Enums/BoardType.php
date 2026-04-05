<?php

namespace App\Enums;

enum BoardType: string
{
    case Company = 'company';
    case Department = 'department';
    case Project = 'project';

    public function label(): string
    {
        return match ($this) {
            self::Company => 'Company',
            self::Department => 'Department',
            self::Project => 'Project',
        };
    }
}

<?php

namespace App\Enums;

enum EmploymentType: string
{
    case FullTime = 'full_time';
    case PartTime = 'part_time';
    case Contract = 'contract';
    case Intern = 'intern';
    case Freelance = 'freelance';

    public function label(): string
    {
        return match ($this) {
            self::FullTime => 'Full Time',
            self::PartTime => 'Part Time',
            self::Contract => 'Contract',
            self::Intern => 'Intern',
            self::Freelance => 'Freelance',
        };
    }
}

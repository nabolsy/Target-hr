<?php

namespace App\Enums;

enum PerformanceRating: string
{
    case Exceptional = 'exceptional';
    case ExceedsExpectations = 'exceeds_expectations';
    case MeetsExpectations = 'meets_expectations';
    case BelowExpectations = 'below_expectations';
    case Unsatisfactory = 'unsatisfactory';

    public function label(): string
    {
        return match ($this) {
            self::Exceptional => 'Exceptional',
            self::ExceedsExpectations => 'Exceeds Expectations',
            self::MeetsExpectations => 'Meets Expectations',
            self::BelowExpectations => 'Below Expectations',
            self::Unsatisfactory => 'Unsatisfactory',
        };
    }

    /**
     * Determine rating band from a numeric score.
     *
     * Exceptional >= 4.5
     * Exceeds Expectations >= 3.5
     * Meets Expectations >= 2.5
     * Below Expectations >= 1.5
     * Unsatisfactory < 1.5
     */
    public static function fromScore(float $score): self
    {
        return match (true) {
            $score >= 4.5 => self::Exceptional,
            $score >= 3.5 => self::ExceedsExpectations,
            $score >= 2.5 => self::MeetsExpectations,
            $score >= 1.5 => self::BelowExpectations,
            default => self::Unsatisfactory,
        };
    }
}

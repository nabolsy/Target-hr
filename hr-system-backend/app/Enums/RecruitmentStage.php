<?php

namespace App\Enums;

enum RecruitmentStage: string
{
    case Applied = 'applied';
    case Screening = 'screening';
    case Interview = 'interview';
    case TechnicalTest = 'technical_test';
    case HrInterview = 'hr_interview';
    case Offer = 'offer';
    case Hired = 'hired';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::Applied => 'Applied',
            self::Screening => 'Screening',
            self::Interview => 'Interview',
            self::TechnicalTest => 'Technical Test',
            self::HrInterview => 'HR Interview',
            self::Offer => 'Offer',
            self::Hired => 'Hired',
            self::Rejected => 'Rejected',
        };
    }

    /**
     * Get the ordered pipeline stages (excluding terminal states).
     */
    public static function pipeline(): array
    {
        return [
            self::Applied,
            self::Screening,
            self::Interview,
            self::TechnicalTest,
            self::HrInterview,
            self::Offer,
        ];
    }

    /**
     * Get the next valid stages from the current stage.
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Applied => [self::Screening, self::Rejected],
            self::Screening => [self::Interview, self::Rejected],
            self::Interview => [self::TechnicalTest, self::Rejected],
            self::TechnicalTest => [self::HrInterview, self::Rejected],
            self::HrInterview => [self::Offer, self::Rejected],
            self::Offer => [self::Hired, self::Rejected],
            self::Hired, self::Rejected => [],
        };
    }

    public function canTransitionTo(self $target): bool
    {
        return in_array($target, $this->allowedTransitions());
    }

    public function isTerminal(): bool
    {
        return in_array($this, [self::Hired, self::Rejected]);
    }
}

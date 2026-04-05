<?php

namespace App\Enums;

enum DocumentType: string
{
    case NationalId = 'national_id';
    case Passport = 'passport';
    case Contract = 'contract';
    case CV = 'cv';
    case Certificate = 'certificate';
    case SalaryDocument = 'salary_document';
    case DisciplinaryLetter = 'disciplinary_letter';
    case AppraisalForm = 'appraisal_form';
    case SignedPolicy = 'signed_policy';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::NationalId => 'National ID',
            self::Passport => 'Passport',
            self::Contract => 'Contract',
            self::CV => 'CV',
            self::Certificate => 'Certificate',
            self::SalaryDocument => 'Salary Document',
            self::DisciplinaryLetter => 'Disciplinary Letter',
            self::AppraisalForm => 'Appraisal Form',
            self::SignedPolicy => 'Signed Policy',
            self::Other => 'Other',
        };
    }
}

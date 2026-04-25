<?php

namespace App\DTOs;

use App\Enums\CandidateStatus;
use App\Enums\RecruitmentStage;

readonly class CandidateDTO
{
    public function __construct(
        public ?int $companyId = null,
        public ?int $jobOpeningId = null,
        public ?string $firstName = null,
        public ?string $lastName = null,
        public ?string $email = null,
        public ?string $phone = null,
        public ?string $cvPath = null,
        public ?string $coverLetter = null,
        public ?RecruitmentStage $stage = null,
        public ?CandidateStatus $status = null,
        public ?string $source = null,
        public ?string $appliedAt = null,
        public ?string $notes = null,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            companyId: $data['company_id'] ?? null,
            jobOpeningId: $data['job_opening_id'] ?? null,
            firstName: $data['first_name'] ?? null,
            lastName: $data['last_name'] ?? null,
            email: $data['email'] ?? null,
            phone: $data['phone'] ?? null,
            cvPath: $data['cv_path'] ?? null,
            coverLetter: $data['cover_letter'] ?? null,
            stage: isset($data['stage']) ? RecruitmentStage::from($data['stage']) : null,
            status: isset($data['status']) ? CandidateStatus::from($data['status']) : null,
            source: $data['source'] ?? null,
            appliedAt: $data['applied_at'] ?? null,
            notes: $data['notes'] ?? null,
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'company_id' => $this->companyId,
            'job_opening_id' => $this->jobOpeningId,
            'first_name' => $this->firstName,
            'last_name' => $this->lastName,
            'email' => $this->email,
            'phone' => $this->phone,
            'cv_path' => $this->cvPath,
            'cover_letter' => $this->coverLetter,
            'stage' => $this->stage?->value,
            'status' => $this->status?->value,
            'source' => $this->source,
            'applied_at' => $this->appliedAt,
            'notes' => $this->notes,
        ], fn ($value) => $value !== null);
    }
}

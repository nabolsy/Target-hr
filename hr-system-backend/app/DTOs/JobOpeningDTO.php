<?php

namespace App\DTOs;

use App\Enums\JobOpeningStatus;

readonly class JobOpeningDTO
{
    public function __construct(
        public ?int $companyId = null,
        public ?int $departmentId = null,
        public ?string $title = null,
        public ?string $description = null,
        public ?string $requirements = null,
        public ?string $employmentType = null,
        public ?string $location = null,
        public ?string $salaryRangeMin = null,
        public ?string $salaryRangeMax = null,
        public ?int $positionsCount = null,
        public ?JobOpeningStatus $status = null,
        public ?int $createdBy = null,
        public ?string $publishedAt = null,
        public ?string $closesAt = null,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            companyId: $data['company_id'] ?? null,
            departmentId: $data['department_id'] ?? null,
            title: $data['title'] ?? null,
            description: $data['description'] ?? null,
            requirements: $data['requirements'] ?? null,
            employmentType: $data['employment_type'] ?? null,
            location: $data['location'] ?? null,
            salaryRangeMin: $data['salary_range_min'] ?? null,
            salaryRangeMax: $data['salary_range_max'] ?? null,
            positionsCount: $data['positions_count'] ?? null,
            status: isset($data['status']) ? JobOpeningStatus::from($data['status']) : null,
            createdBy: $data['created_by'] ?? null,
            publishedAt: $data['published_at'] ?? null,
            closesAt: $data['closes_at'] ?? null,
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'company_id' => $this->companyId,
            'department_id' => $this->departmentId,
            'title' => $this->title,
            'description' => $this->description,
            'requirements' => $this->requirements,
            'employment_type' => $this->employmentType,
            'location' => $this->location,
            'salary_range_min' => $this->salaryRangeMin,
            'salary_range_max' => $this->salaryRangeMax,
            'positions_count' => $this->positionsCount,
            'status' => $this->status?->value,
            'created_by' => $this->createdBy,
            'published_at' => $this->publishedAt,
            'closes_at' => $this->closesAt,
        ], fn ($value) => $value !== null);
    }
}

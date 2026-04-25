<?php

namespace App\DTOs;

use App\Models\Designation;

readonly class DesignationDTO
{
    public function __construct(
        public string $name,
        public int $companyId,
        public ?string $nameAr = null,
        public ?int $departmentId = null,
        public ?string $description = null,
        public ?string $grade = null,
        public ?int $level = null,
        public ?bool $isActive = null,
    ) {
    }

    public static function fromArray(array $data): self
    {
        // Derive `level` from `grade` so the FE only has to send the
        // string. If both are sent we prefer the explicit level, which
        // gives admins escape-hatch flexibility for custom titles.
        $grade = isset($data['grade']) ? strtolower($data['grade']) : null;
        $level = $data['level'] ?? null;
        if ($level === null && $grade !== null && isset(Designation::GRADES[$grade])) {
            $level = Designation::GRADES[$grade];
        }

        return new self(
            name: $data['name'],
            companyId: $data['company_id'],
            nameAr: $data['name_ar'] ?? null,
            departmentId: array_key_exists('department_id', $data) ? $data['department_id'] : null,
            description: $data['description'] ?? null,
            grade: $grade,
            level: $level,
            isActive: isset($data['is_active']) ? (bool) $data['is_active'] : null,
        );
    }

    public function toArray(): array
    {
        $out = [
            'name' => $this->name,
            'company_id' => $this->companyId,
            'name_ar' => $this->nameAr,
            'department_id' => $this->departmentId,
            'description' => $this->description,
            'grade' => $this->grade,
            'level' => $this->level,
            'is_active' => $this->isActive,
        ];

        return array_filter($out, fn ($value) => $value !== null);
    }
}

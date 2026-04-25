<?php

namespace App\DTOs;

use App\Enums\DepartmentStatus;

readonly class DepartmentDTO
{
    public function __construct(
        public string $name,
        public int $companyId,
        public ?string $nameAr = null,
        public ?string $code = null,
        public ?int $parentId = null,
        public ?string $description = null,
        public ?int $managerId = null,
        public ?int $branchId = null,
        public ?DepartmentStatus $status = null,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'],
            companyId: $data['company_id'],
            nameAr: $data['name_ar'] ?? null,
            code: $data['code'] ?? null,
            parentId: $data['parent_id'] ?? null,
            description: $data['description'] ?? null,
            managerId: $data['manager_id'] ?? null,
            branchId: $data['branch_id'] ?? null,
            status: isset($data['status']) ? DepartmentStatus::from($data['status']) : null,
        );
    }

    public function toArray(): array
    {
        // Preserve explicit null for nullable FK/code so updates can clear them.
        // Only strip keys whose value was never provided (remain undefined in DTO).
        return array_filter([
            'name' => $this->name,
            'name_ar' => $this->nameAr,
            'code' => $this->code,
            'company_id' => $this->companyId,
            'parent_id' => $this->parentId,
            'description' => $this->description,
            'manager_id' => $this->managerId,
            'branch_id' => $this->branchId,
            'status' => $this->status?->value,
        ], fn ($value) => $value !== null);
    }
}

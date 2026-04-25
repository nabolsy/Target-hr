<?php

namespace App\DTOs;

use App\Enums\AssetCondition;
use App\Enums\AssetStatus;

readonly class AssetDTO
{
    public function __construct(
        public ?int $companyId = null,
        public ?string $name = null,
        public ?string $assetCode = null,
        public ?string $category = null,
        public ?string $description = null,
        public ?string $serialNumber = null,
        public ?string $purchaseDate = null,
        public ?string $purchaseCost = null,
        public ?AssetCondition $condition = null,
        public ?AssetStatus $status = null,
        public ?string $location = null,
        public ?string $notes = null,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            companyId: $data['company_id'] ?? null,
            name: $data['name'] ?? null,
            assetCode: $data['asset_code'] ?? null,
            category: $data['category'] ?? null,
            description: $data['description'] ?? null,
            serialNumber: $data['serial_number'] ?? null,
            purchaseDate: $data['purchase_date'] ?? null,
            purchaseCost: $data['purchase_cost'] ?? null,
            condition: isset($data['condition']) ? AssetCondition::from($data['condition']) : null,
            status: isset($data['status']) ? AssetStatus::from($data['status']) : null,
            location: $data['location'] ?? null,
            notes: $data['notes'] ?? null,
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'company_id' => $this->companyId,
            'name' => $this->name,
            'asset_code' => $this->assetCode,
            'category' => $this->category,
            'description' => $this->description,
            'serial_number' => $this->serialNumber,
            'purchase_date' => $this->purchaseDate,
            'purchase_cost' => $this->purchaseCost,
            'condition' => $this->condition?->value,
            'status' => $this->status?->value,
            'location' => $this->location,
            'notes' => $this->notes,
        ], fn ($value) => $value !== null);
    }
}

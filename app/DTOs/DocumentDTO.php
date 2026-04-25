<?php

namespace App\DTOs;

use App\Enums\DocumentType;

readonly class DocumentDTO
{
    public function __construct(
        public int $employeeId,
        public string $title,
        public DocumentType $type,
        public ?string $filePath = null,
        public ?string $fileName = null,
        public ?int $fileSize = null,
        public ?string $mimeType = null,
        public ?string $expiryDate = null,
        public ?string $notes = null,
        public ?int $companyId = null,
        public ?int $uploadedBy = null,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            employeeId: $data['employee_id'],
            title: $data['title'],
            type: isset($data['type']) && $data['type'] instanceof DocumentType
                ? $data['type']
                : DocumentType::from($data['type']),
            filePath: $data['file_path'] ?? null,
            fileName: $data['file_name'] ?? null,
            fileSize: $data['file_size'] ?? null,
            mimeType: $data['mime_type'] ?? null,
            expiryDate: $data['expiry_date'] ?? null,
            notes: $data['notes'] ?? null,
            companyId: $data['company_id'] ?? null,
            uploadedBy: $data['uploaded_by'] ?? null,
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'employee_id' => $this->employeeId,
            'title' => $this->title,
            'type' => $this->type->value,
            'file_path' => $this->filePath,
            'file_name' => $this->fileName,
            'file_size' => $this->fileSize,
            'mime_type' => $this->mimeType,
            'expiry_date' => $this->expiryDate,
            'notes' => $this->notes,
            'company_id' => $this->companyId,
            'uploaded_by' => $this->uploadedBy,
        ], fn ($value) => $value !== null);
    }
}

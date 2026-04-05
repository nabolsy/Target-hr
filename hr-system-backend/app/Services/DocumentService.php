<?php

namespace App\Services;

use App\DTOs\DocumentDTO;
use App\Enums\DocumentStatus;
use App\Enums\DocumentType;
use App\Events\DocumentExpiring;
use App\Events\DocumentUploaded;
use App\Models\EmployeeDocument;
use App\Repositories\Interfaces\DocumentRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DocumentService extends BaseService
{
    public function __construct(
        protected DocumentRepositoryInterface $documentRepository,
    ) {
        parent::__construct($documentRepository);
    }

    public function upload(DocumentDTO $dto, UploadedFile $file): EmployeeDocument
    {
        return DB::transaction(function () use ($dto, $file) {
            $companyId = $dto->companyId ?? auth()->user()->company_id;
            $uuid = Str::uuid()->toString();
            $extension = $file->getClientOriginalExtension();
            $storagePath = "documents/{$companyId}/{$dto->employeeId}/{$uuid}.{$extension}";

            Storage::disk('private')->put($storagePath, file_get_contents($file));

            $data = $dto->toArray();
            $data['company_id'] = $companyId;
            $data['file_path'] = $storagePath;
            $data['file_name'] = $file->getClientOriginalName();
            $data['file_size'] = $file->getSize();
            $data['mime_type'] = $file->getMimeType();
            $data['uploaded_by'] = $dto->uploadedBy ?? auth()->id();
            $data['status'] = DocumentStatus::Active->value;

            $document = $this->documentRepository->create($data);

            event(new DocumentUploaded($document));

            return $document;
        });
    }

    public function download(int $id): string
    {
        $document = $this->documentRepository->findOrFail($id);

        return Storage::disk('private')->temporaryUrl(
            $document->file_path,
            now()->addMinutes(15)
        );
    }

    public function deleteDocument(int $id): bool
    {
        return DB::transaction(function () use ($id) {
            $document = $this->documentRepository->findOrFail($id);

            Storage::disk('private')->delete($document->file_path);

            return $this->documentRepository->delete($id);
        });
    }

    public function getByEmployee(int $employeeId): Collection
    {
        return $this->documentRepository->getByEmployee($employeeId);
    }

    public function paginateWithFilters(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        return $this->documentRepository->paginateWithFilters($filters, $perPage);
    }

    public function getByType(DocumentType $type): Collection
    {
        return $this->documentRepository->getByType($type);
    }

    public function updateExpiryStatuses(): array
    {
        $counts = ['expiring' => 0, 'expired' => 0];

        // Mark documents as expired (past expiry date)
        $expired = $this->documentRepository->getExpired();
        foreach ($expired as $document) {
            $document->update(['status' => DocumentStatus::Expired]);
            $counts['expired']++;
        }

        // Mark documents as expiring (within 30 days of expiry)
        $expiring = $this->documentRepository->getExpiring(30);
        foreach ($expiring as $document) {
            $document->update(['status' => DocumentStatus::Expiring]);
            event(new DocumentExpiring($document));
            $counts['expiring']++;
        }

        return $counts;
    }
}

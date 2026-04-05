<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class DocumentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'company_id' => $this->company_id,
            'employee_id' => $this->employee_id,
            'title' => $this->title,
            'type' => $this->type->value,
            'type_label' => $this->type->label(),
            'file_name' => $this->file_name,
            'file_size' => $this->file_size,
            'mime_type' => $this->mime_type,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'expiry_date' => $this->expiry_date?->toDateString(),
            'is_expired' => $this->expiry_date?->isPast() ?? false,
            'days_until_expiry' => $this->expiry_date
                ? (int) now()->diffInDays($this->expiry_date, false)
                : null,
            'uploaded_by' => $this->uploaded_by,
            'uploader' => $this->whenLoaded('uploader', fn () => [
                'id' => $this->uploader->id,
                'name' => $this->uploader->name,
            ]),
            'notes' => $this->notes,
            'download_url' => $this->generateDownloadUrl(),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }

    private function generateDownloadUrl(): ?string
    {
        try {
            return Storage::disk('private')->temporaryUrl(
                $this->file_path,
                now()->addMinutes(15)
            );
        } catch (\RuntimeException) {
            // Driver does not support temporary URLs (e.g. local disk without URL generation)
            return route('api.v1.documents.download', $this->id);
        }
    }
}

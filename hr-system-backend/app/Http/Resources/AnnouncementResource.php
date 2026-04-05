<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AnnouncementResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $user = $request->user();
        $readRecord = $user
            ? $this->reads->firstWhere('user_id', $user->id)
            : null;

        return [
            'id' => $this->id,
            'company_id' => $this->company_id,
            'department_id' => $this->department_id,
            'title' => $this->title,
            'body' => $this->body,
            'type' => $this->type->value,
            'type_label' => $this->type->label(),
            'is_pinned' => $this->is_pinned,
            'requires_acknowledgement' => $this->requires_acknowledgement,
            'is_published' => $this->is_published,
            'is_expired' => $this->is_expired,
            'published_at' => $this->published_at?->toISOString(),
            'expires_at' => $this->expires_at?->toISOString(),
            'created_by' => $this->created_by,
            'creator' => $this->whenLoaded('creator', fn () => [
                'id' => $this->creator->id,
                'name' => $this->creator->name,
            ]),
            'department' => $this->whenLoaded('department', fn () => [
                'id' => $this->department->id,
                'name' => $this->department->name,
            ]),
            'is_read' => $readRecord !== null,
            'is_acknowledged' => $readRecord?->acknowledged_at !== null,
            'read_at' => $readRecord?->read_at?->toISOString(),
            'acknowledged_at' => $readRecord?->acknowledged_at?->toISOString(),
            'read_count' => $this->whenCounted('reads'),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}

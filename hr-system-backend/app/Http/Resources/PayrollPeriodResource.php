<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PayrollPeriodResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'company_id' => $this->company_id,
            'month' => $this->month,
            'year' => $this->year,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'generated_by' => $this->generated_by,
            'generated_at' => $this->generated_at?->toISOString(),
            'locked_at' => $this->locked_at?->toISOString(),
            'records_count' => $this->whenCounted('records'),
            'records' => PayrollRecordResource::collection($this->whenLoaded('records')),
            'generated_by_user' => new UserResource($this->whenLoaded('generatedBy')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}

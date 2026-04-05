<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReviewMetricResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'performance_review_id' => $this->performance_review_id,
            'name' => $this->name,
            'description' => $this->description,
            'weight' => (float) $this->weight,
            'score' => $this->score !== null ? (float) $this->score : null,
            'comments' => $this->comments,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}

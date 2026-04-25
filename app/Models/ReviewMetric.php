<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReviewMetric extends Model
{
    use HasFactory;

    protected $fillable = [
        'performance_review_id',
        'name',
        'description',
        'weight',
        'score',
        'comments',
    ];

    protected function casts(): array
    {
        return [
            'weight' => 'decimal:2',
            'score' => 'decimal:2',
        ];
    }

    // Relationships

    public function performanceReview(): BelongsTo
    {
        return $this->belongsTo(PerformanceReview::class);
    }
}

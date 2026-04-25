<?php

namespace App\Models;

use App\Enums\PerformanceRating;
use App\Enums\ReviewStatus;
use App\Traits\Auditable;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class PerformanceReview extends Model
{
    use HasFactory, SoftDeletes, Auditable, BelongsToTenant;

    protected $fillable = [
        'company_id',
        'review_cycle_id',
        'employee_id',
        'reviewer_id',
        'type',
        'overall_score',
        'rating',
        'status',
        'manager_comments',
        'employee_comments',
        'goals_for_next_period',
        'development_plan',
        'promotion_recommendation',
        'submitted_at',
        'acknowledged_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => ReviewStatus::class,
            'overall_score' => 'decimal:2',
            'promotion_recommendation' => 'boolean',
            'submitted_at' => 'datetime',
            'acknowledged_at' => 'datetime',
        ];
    }

    // Relationships

    // Note: company() is provided by BelongsToTenant trait

    public function reviewCycle(): BelongsTo
    {
        return $this->belongsTo(ReviewCycle::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }

    public function metrics(): HasMany
    {
        return $this->hasMany(ReviewMetric::class);
    }

    // Business Logic

    /**
     * Calculate overall score from metrics using weighted average formula.
     * overallScore = sum(score_i * weight_i) / sum(weight_i)
     */
    public function calculateOverallScore(): ?float
    {
        $metrics = $this->metrics()->whereNotNull('score')->get();

        if ($metrics->isEmpty()) {
            return null;
        }

        $weightedSum = $metrics->sum(fn ($metric) => $metric->score * $metric->weight);
        $totalWeight = $metrics->sum('weight');

        if ($totalWeight == 0) {
            return null;
        }

        return round($weightedSum / $totalWeight, 2);
    }

    /**
     * Get the rating band based on the overall score.
     */
    public function getRatingBand(): ?PerformanceRating
    {
        if ($this->overall_score === null) {
            return null;
        }

        return PerformanceRating::fromScore((float) $this->overall_score);
    }

    // Scopes

    public function scopeByEmployee($query, int $employeeId)
    {
        return $query->where('employee_id', $employeeId);
    }

    public function scopeByCycle($query, int $reviewCycleId)
    {
        return $query->where('review_cycle_id', $reviewCycleId);
    }

    public function scopeByStatus($query, ReviewStatus $status)
    {
        return $query->where('status', $status);
    }
}

<?php

namespace App\Models;

use App\Enums\ReviewStatus;
use App\Enums\ReviewType;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ReviewCycle extends Model
{
    use HasFactory, BelongsToTenant;

    protected $fillable = [
        'company_id',
        'name',
        'type',
        'start_date',
        'end_date',
        'status',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'type' => ReviewType::class,
            'status' => ReviewStatus::class,
            'start_date' => 'date',
            'end_date' => 'date',
        ];
    }

    // Relationships

    // Note: company() is provided by BelongsToTenant trait

    public function reviews(): HasMany
    {
        return $this->hasMany(PerformanceReview::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function goals(): HasMany
    {
        return $this->hasMany(Goal::class);
    }

    // Scopes

    public function scopeActive($query)
    {
        return $query->where('status', ReviewStatus::Active);
    }

    public function scopeByType($query, ReviewType $type)
    {
        return $query->where('type', $type);
    }
}

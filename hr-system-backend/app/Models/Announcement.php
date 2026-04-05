<?php

namespace App\Models;

use App\Enums\AnnouncementType;
use App\Traits\Auditable;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Announcement extends Model
{
    use HasFactory, SoftDeletes, BelongsToTenant, Auditable;

    protected $fillable = [
        'company_id',
        'department_id',
        'title',
        'body',
        'type',
        'is_pinned',
        'requires_acknowledgement',
        'published_at',
        'expires_at',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'type' => AnnouncementType::class,
            'is_pinned' => 'boolean',
            'requires_acknowledgement' => 'boolean',
            'published_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    // Relationships

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function reads(): HasMany
    {
        return $this->hasMany(AnnouncementRead::class);
    }

    public function readByUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'announcement_reads')
            ->withPivot(['read_at', 'acknowledged_at'])
            ->withTimestamps();
    }

    // Scopes

    public function scopePublished(Builder $query): Builder
    {
        return $query->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where(function (Builder $q) {
            $q->whereNull('expires_at')
                ->orWhere('expires_at', '>', now());
        });
    }

    public function scopePinned(Builder $query): Builder
    {
        return $query->where('is_pinned', true);
    }

    public function scopeByType(Builder $query, AnnouncementType $type): Builder
    {
        return $query->where('type', $type);
    }

    public function scopeForDepartment(Builder $query, ?int $departmentId): Builder
    {
        return $query->where(function (Builder $q) use ($departmentId) {
            $q->whereNull('department_id');

            if ($departmentId) {
                $q->orWhere('department_id', $departmentId);
            }
        });
    }

    // Accessors

    public function getIsPublishedAttribute(): bool
    {
        return $this->published_at !== null && $this->published_at->lte(now());
    }

    public function getIsExpiredAttribute(): bool
    {
        return $this->expires_at !== null && $this->expires_at->lt(now());
    }
}

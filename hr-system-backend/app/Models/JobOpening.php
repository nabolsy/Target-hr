<?php

namespace App\Models;

use App\Enums\JobOpeningStatus;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class JobOpening extends Model
{
    use HasFactory, SoftDeletes, BelongsToTenant;

    protected $fillable = [
        'company_id',
        'department_id',
        'title',
        'description',
        'requirements',
        'employment_type',
        'location',
        'salary_range_min',
        'salary_range_max',
        'positions_count',
        'status',
        'created_by',
        'published_at',
        'closes_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => JobOpeningStatus::class,
            'salary_range_min' => 'decimal:2',
            'salary_range_max' => 'decimal:2',
            'positions_count' => 'integer',
            'published_at' => 'datetime',
            'closes_at' => 'datetime',
        ];
    }

    // Relationships

    // Note: company() is provided by BelongsToTenant trait

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function candidates(): HasMany
    {
        return $this->hasMany(Candidate::class);
    }

    // Scopes

    public function scopeOpen($query)
    {
        return $query->where('status', JobOpeningStatus::Open);
    }

    public function scopeActive($query)
    {
        return $query->whereIn('status', [JobOpeningStatus::Open, JobOpeningStatus::Draft]);
    }
}

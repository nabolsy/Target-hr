<?php

namespace App\Models;

use App\Enums\CandidateStatus;
use App\Enums\RecruitmentStage;
use App\Traits\Auditable;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\SoftDeletes;

class Candidate extends Model
{
    use HasFactory, SoftDeletes, Auditable, BelongsToTenant;

    protected $fillable = [
        'company_id',
        'job_opening_id',
        'first_name',
        'last_name',
        'email',
        'phone',
        'cv_path',
        'cover_letter',
        'stage',
        'status',
        'source',
        'applied_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'stage' => RecruitmentStage::class,
            'status' => CandidateStatus::class,
            'applied_at' => 'datetime',
        ];
    }

    // Relationships

    // Note: company() is provided by BelongsToTenant trait

    public function jobOpening(): BelongsTo
    {
        return $this->belongsTo(JobOpening::class);
    }

    public function interviews(): HasMany
    {
        return $this->hasMany(Interview::class);
    }

    public function feedback(): HasManyThrough
    {
        return $this->hasManyThrough(InterviewFeedback::class, Interview::class);
    }

    // Scopes

    public function scopeActive($query)
    {
        return $query->where('status', CandidateStatus::Active);
    }

    public function scopeByStage($query, RecruitmentStage $stage)
    {
        return $query->where('stage', $stage);
    }

    public function scopeByJobOpening($query, int $jobOpeningId)
    {
        return $query->where('job_opening_id', $jobOpeningId);
    }

    // Accessors

    public function getFullNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }
}

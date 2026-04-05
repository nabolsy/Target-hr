<?php

namespace App\Models;

use App\Enums\InterviewType;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Interview extends Model
{
    use HasFactory, BelongsToTenant;

    protected $fillable = [
        'company_id',
        'candidate_id',
        'interviewer_id',
        'scheduled_at',
        'duration_minutes',
        'type',
        'location',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'type' => InterviewType::class,
            'scheduled_at' => 'datetime',
            'duration_minutes' => 'integer',
        ];
    }

    // Relationships

    // Note: company() is provided by BelongsToTenant trait

    public function candidate(): BelongsTo
    {
        return $this->belongsTo(Candidate::class);
    }

    public function interviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'interviewer_id');
    }

    public function feedback(): HasMany
    {
        return $this->hasMany(InterviewFeedback::class);
    }

    // Scopes

    public function scopeScheduled($query)
    {
        return $query->where('status', 'scheduled');
    }

    public function scopeUpcoming($query)
    {
        return $query->where('scheduled_at', '>=', now())
            ->where('status', 'scheduled');
    }
}

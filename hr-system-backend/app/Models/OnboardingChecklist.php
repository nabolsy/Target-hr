<?php

namespace App\Models;

use App\Enums\ChecklistStatus;
use App\Enums\OnboardingType;
use App\Traits\Auditable;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OnboardingChecklist extends Model
{
    use HasFactory, Auditable, BelongsToTenant;

    protected $fillable = [
        'company_id',
        'employee_id',
        'template_id',
        'type',
        'status',
        'started_at',
        'completed_at',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'type' => OnboardingType::class,
            'status' => ChecklistStatus::class,
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    // Relationships

    // Note: company() is provided by BelongsToTenant trait

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(OnboardingTemplate::class, 'template_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(OnboardingChecklistItem::class, 'checklist_id')->orderBy('sort_order');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Scopes

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', ChecklistStatus::Pending);
    }

    public function scopeInProgress(Builder $query): Builder
    {
        return $query->where('status', ChecklistStatus::InProgress);
    }

    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('status', ChecklistStatus::Completed);
    }

    public function scopeOnboarding(Builder $query): Builder
    {
        return $query->where('type', OnboardingType::Onboarding);
    }

    public function scopeOffboarding(Builder $query): Builder
    {
        return $query->where('type', OnboardingType::Offboarding);
    }

    // Methods

    public function getProgressPercentage(): float
    {
        $totalItems = $this->items()->count();

        if ($totalItems === 0) {
            return 0.0;
        }

        $completedItems = $this->items()->where('is_completed', true)->count();

        return round(($completedItems / $totalItems) * 100, 2);
    }
}

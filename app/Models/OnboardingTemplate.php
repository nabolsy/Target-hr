<?php

namespace App\Models;

use App\Enums\OnboardingType;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OnboardingTemplate extends Model
{
    use HasFactory, BelongsToTenant;

    protected $fillable = [
        'company_id',
        'department_id',
        'name',
        'type',
        'is_default',
    ];

    protected function casts(): array
    {
        return [
            'type' => OnboardingType::class,
            'is_default' => 'boolean',
        ];
    }

    // Relationships

    // Note: company() is provided by BelongsToTenant trait

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OnboardingTemplateItem::class, 'template_id')->orderBy('sort_order');
    }

    // Scopes

    public function scopeForOnboarding(Builder $query): Builder
    {
        return $query->where('type', OnboardingType::Onboarding);
    }

    public function scopeForOffboarding(Builder $query): Builder
    {
        return $query->where('type', OnboardingType::Offboarding);
    }

    public function scopeDefault(Builder $query): Builder
    {
        return $query->where('is_default', true);
    }
}

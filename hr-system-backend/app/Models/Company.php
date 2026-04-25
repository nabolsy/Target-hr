<?php

namespace App\Models;

use App\Enums\CompanyStatus;
use App\Enums\SubscriptionPlan;
use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Company extends Model
{
    use HasFactory, SoftDeletes, Auditable;

    protected $fillable = [
        'name', 'email', 'phone', 'address', 'city', 'state', 'country',
        'postal_code', 'website', 'logo', 'industry', 'employee_limit',
        'status', 'subscription_plan',
        // SaaS fields
        'plan_id', 'subscription_status', 'trial_ends_at', 'is_active',
        'registered_at', 'settings',
    ];

    protected function casts(): array
    {
        return [
            'status' => CompanyStatus::class,
            'subscription_plan' => SubscriptionPlan::class,
            'employee_limit' => 'integer',
            'is_active' => 'boolean',
            'trial_ends_at' => 'datetime',
            'registered_at' => 'datetime',
            'settings' => 'array',
        ];
    }

    // Relationships
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function activeSubscription(): HasOne
    {
        return $this->hasOne(Subscription::class)->whereIn('status', ['active', 'trial'])->latest();
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', CompanyStatus::Active);
    }

    public function scopeByPlan($query, SubscriptionPlan $plan)
    {
        return $query->where('subscription_plan', $plan);
    }

    // Helpers
    public function hasActiveSubscription(): bool
    {
        return $this->activeSubscription !== null;
    }

    public function isOnTrial(): bool
    {
        return $this->subscription_status === 'trial' && $this->trial_ends_at?->isFuture();
    }
}

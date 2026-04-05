<?php

namespace App\Models;

use App\Enums\CompanyStatus;
use App\Enums\SubscriptionPlan;
use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Company extends Model
{
    use HasFactory, SoftDeletes, Auditable;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'address',
        'city',
        'state',
        'country',
        'postal_code',
        'website',
        'logo',
        'industry',
        'employee_limit',
        'status',
        'subscription_plan',
    ];

    protected function casts(): array
    {
        return [
            'status' => CompanyStatus::class,
            'subscription_plan' => SubscriptionPlan::class,
            'employee_limit' => 'integer',
        ];
    }

    // Relationships
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
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

    // Accessors
    public function getIsActiveAttribute(): bool
    {
        return $this->status === CompanyStatus::Active;
    }
}

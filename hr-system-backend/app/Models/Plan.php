<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Plan extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'slug', 'description', 'price_monthly', 'price_yearly',
        'currency', 'max_employees', 'max_departments', 'max_storage_gb',
        'features', 'is_active', 'is_popular', 'sort_order', 'trial_days',
    ];

    protected function casts(): array
    {
        return [
            'price_monthly' => 'decimal:2',
            'price_yearly' => 'decimal:2',
            'max_employees' => 'integer',
            'max_departments' => 'integer',
            'max_storage_gb' => 'integer',
            'features' => 'array',
            'is_active' => 'boolean',
            'is_popular' => 'boolean',
            'sort_order' => 'integer',
            'trial_days' => 'integer',
        ];
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function companies(): HasMany
    {
        return $this->hasMany(Company::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function isUnlimited(string $field): bool
    {
        return $this->{$field} === -1;
    }
}

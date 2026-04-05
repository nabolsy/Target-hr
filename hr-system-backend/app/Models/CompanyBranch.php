<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CompanyBranch extends Model
{
    use HasFactory, SoftDeletes, BelongsToTenant;

    protected $fillable = [
        'company_id',
        'name',
        'address',
        'city',
        'state',
        'country',
        'postal_code',
        'phone',
        'email',
        'is_headquarters',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_headquarters' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    // Scopes

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeHeadquarters(Builder $query): Builder
    {
        return $query->where('is_headquarters', true);
    }
}

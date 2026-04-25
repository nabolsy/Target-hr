<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class LeaveType extends Model
{
    use HasFactory, SoftDeletes, BelongsToTenant;

    protected $fillable = [
        'company_id',
        'name',
        'name_ar',
        'slug',
        'days_per_year',
        'is_paid',
        'requires_attachment',
        'allows_half_day',
        'is_active',
        'description',
        'color',
    ];

    protected function casts(): array
    {
        return [
            'days_per_year' => 'decimal:1',
            'is_paid' => 'boolean',
            'requires_attachment' => 'boolean',
            'allows_half_day' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    // Relationships

    // Note: company() is provided by BelongsToTenant trait

    public function leaveBalances(): HasMany
    {
        return $this->hasMany(LeaveBalance::class);
    }

    public function leaveRequests(): HasMany
    {
        return $this->hasMany(LeaveRequest::class);
    }

    // Scopes

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}

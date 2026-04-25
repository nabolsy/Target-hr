<?php

namespace App\Models;

use App\Enums\AssetCategory;
use App\Enums\AssetCondition;
use App\Enums\AssetStatus;
use App\Traits\Auditable;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Illuminate\Database\Eloquent\SoftDeletes;

class Asset extends Model
{
    use HasFactory, SoftDeletes, Auditable, BelongsToTenant;

    protected $fillable = [
        'company_id',
        'name',
        'asset_code',
        'category',
        'description',
        'serial_number',
        'purchase_date',
        'purchase_cost',
        'condition',
        'status',
        'location',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'category' => AssetCategory::class,
            'condition' => AssetCondition::class,
            'status' => AssetStatus::class,
            'purchase_date' => 'date',
            'purchase_cost' => 'decimal:2',
        ];
    }

    // Relationships

    // Note: company() is provided by BelongsToTenant trait

    public function assignments(): HasMany
    {
        return $this->hasMany(AssetAssignment::class);
    }

    public function currentAssignment(): HasOne
    {
        return $this->hasOne(AssetAssignment::class)
            ->whereNull('returned_at')
            ->latestOfMany('assigned_at');
    }

    public function currentEmployee(): HasOneThrough
    {
        return $this->hasOneThrough(
            Employee::class,
            AssetAssignment::class,
            'asset_id',
            'id',
            'id',
            'employee_id'
        )->whereNull('asset_assignments.returned_at');
    }

    // Scopes

    public function scopeAvailable($query)
    {
        return $query->where('status', AssetStatus::Available);
    }

    public function scopeAssigned($query)
    {
        return $query->where('status', AssetStatus::Assigned);
    }

    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }
}

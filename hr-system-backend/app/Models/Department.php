<?php

namespace App\Models;

use App\Enums\DepartmentStatus;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Department extends Model
{
    use HasFactory, SoftDeletes, BelongsToTenant;

    protected $fillable = [
        'company_id',
        'parent_id',
        'name',
        'description',
        'manager_id',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'status' => DepartmentStatus::class,
        ];
    }

    // Relationships

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Department::class, 'parent_id');
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    public function employees(): HasMany
    {
        return $this->hasMany(User::class, 'department_id');
    }

    public function designations(): HasMany
    {
        return $this->hasMany(Designation::class);
    }

    // Scopes

    public function scopeActive($query)
    {
        return $query->where('status', DepartmentStatus::Active);
    }

    public function scopeRoot($query)
    {
        return $query->whereNull('parent_id');
    }

    // Accessors

    public function getIsActiveAttribute(): bool
    {
        return $this->status === DepartmentStatus::Active;
    }
}

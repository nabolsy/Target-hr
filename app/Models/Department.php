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
        'name_ar',
        'code',
        'description',
        'manager_id',
        'branch_id',
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

    public function branch(): BelongsTo
    {
        return $this->belongsTo(CompanyBranch::class, 'branch_id');
    }

    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class, 'department_id');
    }

    public function designations(): HasMany
    {
        return $this->hasMany(Designation::class);
    }

    /**
     * Resolve the employee record backing the manager user, if any.
     * Useful for department-scoped access helpers that operate on employees
     * rather than users. Non-breaking — the column still references users.id.
     */
    public function managerEmployee(): ?Employee
    {
        if (! $this->manager_id) {
            return null;
        }

        return Employee::where('user_id', $this->manager_id)->first();
    }

    // Scopes

    public function scopeActive($query)
    {
        return $query->where('status', DepartmentStatus::Active);
    }

    public function scopeInactive($query)
    {
        return $query->where('status', DepartmentStatus::Inactive);
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

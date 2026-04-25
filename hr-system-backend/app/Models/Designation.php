<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Designation extends Model
{
    use HasFactory, SoftDeletes, BelongsToTenant;

    /**
     * Canonical grade values + their default sort levels. The string
     * `grade` is what users see; the integer `level` is what queries
     * sort by. Keeping both gives us the best of both worlds.
     */
    public const GRADES = [
        'junior'   => 1,
        'mid'      => 2,
        'senior'   => 3,
        'lead'     => 4,
        'manager'  => 5,
        'director' => 6,
    ];

    protected $fillable = [
        'company_id',
        'department_id',
        'name',
        'name_ar',
        'description',
        'level',
        'grade',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'level' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class, 'designation_id');
    }

    public function scopeOrderByLevel($query, string $direction = 'asc')
    {
        return $query->orderBy('level', $direction);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}

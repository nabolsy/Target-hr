<?php

namespace App\Models;

use App\Enums\BoardType;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\SoftDeletes;

class Board extends Model
{
    use HasFactory, SoftDeletes, BelongsToTenant;

    protected $fillable = [
        'company_id',
        'name',
        'description',
        'department_id',
        'type',
        'is_archived',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'type' => BoardType::class,
            'is_archived' => 'boolean',
        ];
    }

    // Relationships

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function columns(): HasMany
    {
        return $this->hasMany(BoardColumn::class)->orderBy('sort_order');
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    // Scopes

    public function scopeActive($query)
    {
        return $query->where('is_archived', false);
    }

    public function scopeByType($query, BoardType $type)
    {
        return $query->where('type', $type);
    }

    public function scopeByDepartment($query, int $departmentId)
    {
        return $query->where('department_id', $departmentId);
    }
}

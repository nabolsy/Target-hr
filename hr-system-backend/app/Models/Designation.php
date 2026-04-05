<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Designation extends Model
{
    use HasFactory, SoftDeletes, BelongsToTenant;

    protected $fillable = [
        'company_id',
        'name',
        'description',
        'level',
    ];

    protected function casts(): array
    {
        return [
            'level' => 'integer',
        ];
    }

    // Relationships

    public function employees(): HasMany
    {
        return $this->hasMany(User::class, 'designation_id');
    }

    // Scopes

    public function scopeOrderByLevel($query, string $direction = 'asc')
    {
        return $query->orderBy('level', $direction);
    }
}

<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Shift extends Model
{
    use HasFactory, BelongsToTenant;

    protected $fillable = [
        'company_id',
        'name',
        'start_time',
        'end_time',
        'break_duration_minutes',
        'is_default',
    ];

    protected function casts(): array
    {
        return [
            'start_time' => 'string',
            'end_time' => 'string',
            'break_duration_minutes' => 'integer',
            'is_default' => 'boolean',
        ];
    }

    // Relationships
    public function attendanceRecords(): HasMany
    {
        return $this->hasMany(AttendanceRecord::class);
    }

    // Scopes
    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }
}

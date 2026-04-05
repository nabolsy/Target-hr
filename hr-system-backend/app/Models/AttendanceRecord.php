<?php

namespace App\Models;

use App\Enums\AttendanceStatus;
use App\Traits\Auditable;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class AttendanceRecord extends Model
{
    use HasFactory, BelongsToTenant, Auditable, SoftDeletes;

    protected $fillable = [
        'company_id',
        'employee_id',
        'date',
        'check_in',
        'check_out',
        'shift_id',
        'status',
        'worked_hours',
        'overtime_hours',
        'break_minutes',
        'notes',
        'ip_address',
    ];

    protected function casts(): array
    {
        return [
            'status' => AttendanceStatus::class,
            'date' => 'date',
            'check_in' => 'datetime',
            'check_out' => 'datetime',
            'worked_hours' => 'decimal:2',
            'overtime_hours' => 'decimal:2',
            'break_minutes' => 'integer',
        ];
    }

    // Relationships
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class);
    }

    public function adjustmentRequests(): HasMany
    {
        return $this->hasMany(AttendanceAdjustmentRequest::class);
    }

    // Scopes
    public function scopeForDate($query, $date)
    {
        return $query->whereDate('date', $date);
    }

    public function scopeForEmployee($query, int $employeeId)
    {
        return $query->where('employee_id', $employeeId);
    }

    public function scopeForMonth($query, int $month, int $year)
    {
        return $query->whereMonth('date', $month)->whereYear('date', $year);
    }
}

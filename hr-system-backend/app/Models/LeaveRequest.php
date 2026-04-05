<?php

namespace App\Models;

use App\Enums\LeaveRequestStatus;
use App\Traits\Auditable;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class LeaveRequest extends Model
{
    use HasFactory, SoftDeletes, Auditable, BelongsToTenant;

    protected $fillable = [
        'company_id',
        'employee_id',
        'leave_type_id',
        'start_date',
        'end_date',
        'is_half_day',
        'total_days',
        'reason',
        'attachment_path',
        'status',
        'approved_by',
        'approved_at',
        'rejection_reason',
    ];

    protected function casts(): array
    {
        return [
            'status' => LeaveRequestStatus::class,
            'start_date' => 'date',
            'end_date' => 'date',
            'is_half_day' => 'boolean',
            'total_days' => 'decimal:1',
            'approved_at' => 'datetime',
        ];
    }

    // Relationships

    // Note: company() is provided by BelongsToTenant trait

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function leaveType(): BelongsTo
    {
        return $this->belongsTo(LeaveType::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    // Scopes

    public function scopePending($query)
    {
        return $query->where('status', LeaveRequestStatus::Pending);
    }

    public function scopeApproved($query)
    {
        return $query->where('status', LeaveRequestStatus::Approved);
    }

    public function scopeForEmployee($query, int $employeeId)
    {
        return $query->where('employee_id', $employeeId);
    }

    public function scopeForYear($query, int $year)
    {
        return $query->whereYear('start_date', $year);
    }
}

<?php

namespace App\Models;

use App\Enums\AdjustmentRequestStatus;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceAdjustmentRequest extends Model
{
    use HasFactory, BelongsToTenant;

    protected $fillable = [
        'company_id',
        'employee_id',
        'attendance_record_id',
        'requested_check_in',
        'requested_check_out',
        'reason',
        'status',
        'reviewed_by',
        'reviewed_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => AdjustmentRequestStatus::class,
            'requested_check_in' => 'datetime',
            'requested_check_out' => 'datetime',
            'reviewed_at' => 'datetime',
        ];
    }

    // Relationships
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function attendanceRecord(): BelongsTo
    {
        return $this->belongsTo(AttendanceRecord::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}

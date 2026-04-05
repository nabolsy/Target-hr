<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeaveBalance extends Model
{
    use HasFactory, BelongsToTenant;

    protected $fillable = [
        'company_id',
        'employee_id',
        'leave_type_id',
        'year',
        'total_days',
        'used_days',
        'remaining_days',
    ];

    protected function casts(): array
    {
        return [
            'year' => 'integer',
            'total_days' => 'decimal:1',
            'used_days' => 'decimal:1',
            'remaining_days' => 'decimal:1',
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
}

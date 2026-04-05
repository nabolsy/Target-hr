<?php

namespace App\Models;

use App\Enums\GoalStatus;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Goal extends Model
{
    use HasFactory, BelongsToTenant;

    protected $fillable = [
        'company_id',
        'employee_id',
        'review_cycle_id',
        'title',
        'description',
        'target_value',
        'current_value',
        'unit',
        'status',
        'due_date',
    ];

    protected function casts(): array
    {
        return [
            'status' => GoalStatus::class,
            'due_date' => 'date',
        ];
    }

    // Relationships

    // Note: company() is provided by BelongsToTenant trait

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function reviewCycle(): BelongsTo
    {
        return $this->belongsTo(ReviewCycle::class);
    }

    // Scopes

    public function scopeByEmployee($query, int $employeeId)
    {
        return $query->where('employee_id', $employeeId);
    }

    public function scopeByStatus($query, GoalStatus $status)
    {
        return $query->where('status', $status);
    }
}

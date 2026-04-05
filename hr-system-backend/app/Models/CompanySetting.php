<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CompanySetting extends Model
{
    use HasFactory, BelongsToTenant;

    protected $fillable = [
        'company_id',
        'work_start_time',
        'work_end_time',
        'work_days',
        'timezone',
        'date_format',
        'currency',
        'grace_period_minutes',
        'allow_remote_checkin',
        'leave_approval_levels',
        'probation_period_days',
    ];

    protected function casts(): array
    {
        return [
            'work_days' => 'array',
            'allow_remote_checkin' => 'boolean',
            'grace_period_minutes' => 'integer',
            'leave_approval_levels' => 'integer',
            'probation_period_days' => 'integer',
        ];
    }
}

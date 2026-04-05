<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PayrollRecord extends Model
{
    use HasFactory, BelongsToTenant;

    protected $fillable = [
        'company_id',
        'payroll_period_id',
        'employee_id',
        'basic_salary',
        'total_allowances',
        'total_deductions',
        'gross_salary',
        'net_salary',
        'working_days',
        'present_days',
        'absent_days',
        'leave_days',
        'overtime_hours',
        'notes',
    ];

    protected $hidden = [
        'basic_salary',
        'total_allowances',
        'total_deductions',
        'gross_salary',
        'net_salary',
    ];

    protected function casts(): array
    {
        return [
            'basic_salary' => 'decimal:2',
            'total_allowances' => 'decimal:2',
            'total_deductions' => 'decimal:2',
            'gross_salary' => 'decimal:2',
            'net_salary' => 'decimal:2',
            'working_days' => 'integer',
            'present_days' => 'integer',
            'absent_days' => 'integer',
            'leave_days' => 'integer',
            'overtime_hours' => 'decimal:2',
        ];
    }

    // Relationships

    // Note: company() is provided by BelongsToTenant trait

    public function payrollPeriod(): BelongsTo
    {
        return $this->belongsTo(PayrollPeriod::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}

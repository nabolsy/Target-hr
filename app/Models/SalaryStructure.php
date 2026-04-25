<?php

namespace App\Models;

use App\Enums\PaymentFrequency;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SalaryStructure extends Model
{
    use HasFactory, BelongsToTenant;

    protected $fillable = [
        'company_id',
        'employee_id',
        'basic_salary',
        'currency',
        'payment_frequency',
        'effective_date',
    ];

    protected $hidden = [
        'basic_salary',
    ];

    protected function casts(): array
    {
        return [
            'payment_frequency' => PaymentFrequency::class,
            'basic_salary' => 'decimal:2',
            'effective_date' => 'date',
        ];
    }

    // Relationships

    // Note: company() is provided by BelongsToTenant trait

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function components(): HasMany
    {
        return $this->hasMany(SalaryComponent::class);
    }
}

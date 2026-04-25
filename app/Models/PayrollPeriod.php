<?php

namespace App\Models;

use App\Enums\PayrollStatus;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PayrollPeriod extends Model
{
    use HasFactory, BelongsToTenant;

    protected $fillable = [
        'company_id',
        'month',
        'year',
        'status',
        'generated_by',
        'generated_at',
        'locked_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => PayrollStatus::class,
            'month' => 'integer',
            'year' => 'integer',
            'generated_at' => 'datetime',
            'locked_at' => 'datetime',
        ];
    }

    // Relationships

    // Note: company() is provided by BelongsToTenant trait

    public function records(): HasMany
    {
        return $this->hasMany(PayrollRecord::class);
    }

    public function generatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_by');
    }
}

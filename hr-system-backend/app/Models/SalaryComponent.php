<?php

namespace App\Models;

use App\Enums\SalaryComponentType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalaryComponent extends Model
{
    use HasFactory;

    protected $fillable = [
        'salary_structure_id',
        'name',
        'type',
        'amount',
        'is_percentage',
        'is_taxable',
        'sort_order',
    ];

    protected $hidden = [
        'amount',
    ];

    protected function casts(): array
    {
        return [
            'type' => SalaryComponentType::class,
            'amount' => 'decimal:2',
            'is_percentage' => 'boolean',
            'is_taxable' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    // Relationships

    public function salaryStructure(): BelongsTo
    {
        return $this->belongsTo(SalaryStructure::class);
    }
}

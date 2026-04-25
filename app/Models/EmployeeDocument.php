<?php

namespace App\Models;

use App\Enums\DocumentStatus;
use App\Enums\DocumentType;
use App\Traits\Auditable;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class EmployeeDocument extends Model
{
    use HasFactory, SoftDeletes, Auditable, BelongsToTenant;

    protected $fillable = [
        'company_id',
        'employee_id',
        'title',
        'type',
        'file_path',
        'file_name',
        'file_size',
        'mime_type',
        'status',
        'expiry_date',
        'uploaded_by',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'status' => DocumentStatus::class,
            'type' => DocumentType::class,
            'expiry_date' => 'date',
            'file_size' => 'integer',
        ];
    }

    // Relationships

    public function employee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'employee_id', 'id')
            ->withoutGlobalScopes();
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    // Note: company() relationship is provided by BelongsToTenant trait

    // Scopes

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', DocumentStatus::Active);
    }

    public function scopeExpiring(Builder $query): Builder
    {
        return $query->where('status', DocumentStatus::Expiring);
    }

    public function scopeExpired(Builder $query): Builder
    {
        return $query->where('status', DocumentStatus::Expired);
    }

    public function scopeByType(Builder $query, DocumentType $type): Builder
    {
        return $query->where('type', $type);
    }

    public function scopeForEmployee(Builder $query, int $employeeId): Builder
    {
        return $query->where('employee_id', $employeeId);
    }
}

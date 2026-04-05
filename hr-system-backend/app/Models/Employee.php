<?php

namespace App\Models;

use App\Enums\EmployeeStatus;
use App\Enums\EmploymentType;
use App\Enums\Gender;
use App\Traits\Auditable;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Employee extends Model
{
    use HasFactory, SoftDeletes, Auditable, BelongsToTenant;

    protected $fillable = [
        'company_id',
        'user_id',
        'department_id',
        'designation_id',
        'manager_id',
        'employee_id_number',
        'first_name',
        'last_name',
        'email',
        'phone',
        'date_of_birth',
        'gender',
        'national_id',
        'address',
        'city',
        'state',
        'country',
        'postal_code',
        'profile_image',
        'employment_type',
        'status',
        'join_date',
        'probation_end_date',
        'work_location',
        'salary',
        'bank_name',
        'bank_account_number',
        'emergency_contact_name',
        'emergency_contact_phone',
        'emergency_contact_relation',
        'notes',
    ];

    protected $hidden = [
        'salary',
        'bank_name',
        'bank_account_number',
    ];

    protected function casts(): array
    {
        return [
            'status' => EmployeeStatus::class,
            'employment_type' => EmploymentType::class,
            'gender' => Gender::class,
            'salary' => 'decimal:2',
            'date_of_birth' => 'date',
            'join_date' => 'date',
            'probation_end_date' => 'date',
        ];
    }

    // Relationships

    // Note: company() is provided by BelongsToTenant trait

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function designation(): BelongsTo
    {
        return $this->belongsTo(Designation::class);
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(self::class, 'manager_id');
    }

    public function subordinates(): HasMany
    {
        return $this->hasMany(self::class, 'manager_id');
    }

    public function attendanceRecords(): HasMany
    {
        return $this->hasMany(AttendanceRecord::class);
    }

    public function leaveRequests(): HasMany
    {
        return $this->hasMany(LeaveRequest::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    // Scopes

    public function scopeActive($query)
    {
        return $query->where('status', EmployeeStatus::Active);
    }

    public function scopeByDepartment($query, int $departmentId)
    {
        return $query->where('department_id', $departmentId);
    }

    public function scopeByStatus($query, EmployeeStatus $status)
    {
        return $query->where('status', $status);
    }

    // Accessors

    public function getFullNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }
}

<?php

namespace App\Models;

use App\Enums\TaskPriority;
use App\Traits\Auditable;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Task extends Model
{
    use HasFactory, SoftDeletes, Auditable, BelongsToTenant;

    protected $fillable = [
        'company_id',
        'board_id',
        'column_id',
        'title',
        'description',
        'creator_id',
        'priority',
        'start_date',
        'due_date',
        'estimated_hours',
        'actual_hours',
        'completion_percentage',
        'sort_order',
        'is_archived',
    ];

    protected function casts(): array
    {
        return [
            'priority' => TaskPriority::class,
            'start_date' => 'date',
            'due_date' => 'date',
            'estimated_hours' => 'decimal:2',
            'actual_hours' => 'decimal:2',
            'completion_percentage' => 'integer',
            'sort_order' => 'integer',
            'is_archived' => 'boolean',
        ];
    }

    // Relationships

    public function board(): BelongsTo
    {
        return $this->belongsTo(Board::class);
    }

    public function column(): BelongsTo
    {
        return $this->belongsTo(BoardColumn::class, 'column_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    public function assignees(): BelongsToMany
    {
        return $this->belongsToMany(Employee::class, 'task_assignees')
            ->withPivot('assigned_by', 'assigned_at')
            ->withTimestamps();
    }

    public function labels(): BelongsToMany
    {
        return $this->belongsToMany(TaskLabel::class, 'label_task');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(TaskComment::class);
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(TaskAttachment::class);
    }

    public function checklists(): HasMany
    {
        return $this->hasMany(TaskChecklist::class);
    }

    public function activityLogs(): HasMany
    {
        return $this->hasMany(TaskActivityLog::class);
    }

    public function watchers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'task_watchers')
            ->withTimestamps();
    }

    // Scopes

    public function scopeOverdue($query)
    {
        return $query->whereNotNull('due_date')
            ->where('due_date', '<', now())
            ->whereHas('column', fn ($q) => $q->where('is_done_column', false));
    }

    public function scopeByPriority($query, TaskPriority $priority)
    {
        return $query->where('priority', $priority);
    }

    public function scopeByAssignee($query, int $employeeId)
    {
        return $query->whereHas('assignees', fn ($q) => $q->where('employees.id', $employeeId));
    }

    public function scopeDueThisWeek($query)
    {
        return $query->whereNotNull('due_date')
            ->whereBetween('due_date', [now()->startOfWeek(), now()->endOfWeek()]);
    }
}

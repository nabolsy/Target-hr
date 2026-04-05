<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OnboardingChecklistItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'checklist_id',
        'title',
        'description',
        'is_required',
        'is_completed',
        'completed_by',
        'completed_at',
        'assigned_to',
        'due_date',
        'notes',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_required' => 'boolean',
            'is_completed' => 'boolean',
            'completed_at' => 'datetime',
            'due_date' => 'date',
            'sort_order' => 'integer',
        ];
    }

    // Relationships

    public function checklist(): BelongsTo
    {
        return $this->belongsTo(OnboardingChecklist::class, 'checklist_id');
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function completedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by');
    }
}

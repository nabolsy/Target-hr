<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BoardColumn extends Model
{
    use HasFactory;

    protected $fillable = [
        'board_id',
        'name',
        'sort_order',
        'color',
        'wip_limit',
        'is_done_column',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'wip_limit' => 'integer',
            'is_done_column' => 'boolean',
        ];
    }

    // Relationships

    public function board(): BelongsTo
    {
        return $this->belongsTo(Board::class);
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class, 'column_id')->orderBy('sort_order');
    }
}

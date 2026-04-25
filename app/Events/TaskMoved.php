<?php

namespace App\Events;

use App\Models\BoardColumn;
use App\Models\Task;
use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TaskMoved
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Task $task,
        public BoardColumn $oldColumn,
        public BoardColumn $newColumn,
        public User $user,
    ) {
    }
}

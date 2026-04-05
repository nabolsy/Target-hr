<?php

namespace App\Events;

use App\Enums\EmployeeStatus;
use App\Models\Employee;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class EmployeeStatusChanged
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Employee $employee,
        public EmployeeStatus $oldStatus,
        public EmployeeStatus $newStatus,
    ) {
    }
}

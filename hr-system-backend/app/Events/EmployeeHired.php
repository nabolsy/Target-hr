<?php

namespace App\Events;

use App\Models\Employee;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class EmployeeHired
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Employee $employee)
    {
    }
}

<?php

namespace App\Events;

use App\Models\AttendanceRecord;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class EmployeeCheckedIn
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public AttendanceRecord $attendanceRecord)
    {
    }
}

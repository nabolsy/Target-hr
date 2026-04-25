<?php

namespace App\Events;

use App\Models\Employee;
use App\Models\OnboardingChecklist;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OffboardingCompleted
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public OnboardingChecklist $checklist,
        public Employee $employee,
    ) {
    }
}

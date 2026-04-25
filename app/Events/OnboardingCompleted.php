<?php

namespace App\Events;

use App\Models\OnboardingChecklist;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OnboardingCompleted
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public OnboardingChecklist $checklist)
    {
    }
}

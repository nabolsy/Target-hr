<?php

namespace App\Events;

use App\Models\Candidate;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CandidateHired
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Candidate $candidate)
    {
    }
}

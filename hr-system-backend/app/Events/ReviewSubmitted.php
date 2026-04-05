<?php

namespace App\Events;

use App\Models\PerformanceReview;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ReviewSubmitted
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public PerformanceReview $review)
    {
    }
}

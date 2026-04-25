<?php

namespace App\Events;

use App\Enums\RecruitmentStage;
use App\Models\Candidate;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CandidateStageChanged
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Candidate $candidate,
        public RecruitmentStage $oldStage,
        public RecruitmentStage $newStage,
    ) {
    }
}

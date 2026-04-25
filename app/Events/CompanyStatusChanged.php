<?php

namespace App\Events;

use App\Enums\CompanyStatus;
use App\Models\Company;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CompanyStatusChanged
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Company $company,
        public CompanyStatus $oldStatus,
        public CompanyStatus $newStatus,
    ) {
    }
}

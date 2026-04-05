<?php

namespace App\Events;

use App\Models\Asset;
use App\Models\Employee;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AssetReturned
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Asset $asset,
        public Employee $employee,
    ) {
    }
}

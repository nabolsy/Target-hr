<?php

namespace App\Events;

use App\Models\EmployeeDocument;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DocumentExpiring
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public EmployeeDocument $document)
    {
    }
}

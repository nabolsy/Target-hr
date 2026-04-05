<?php

namespace App\Listeners;

use App\Events\CompanyCreated;
use Illuminate\Support\Facades\Log;

class LogCompanyCreated
{
    public function handle(CompanyCreated $event): void
    {
        Log::info('New company created', [
            'company_id' => $event->company->id,
            'company_name' => $event->company->name,
            'created_by' => auth()->id(),
        ]);
    }
}

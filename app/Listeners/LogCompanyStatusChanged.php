<?php

namespace App\Listeners;

use App\Events\CompanyStatusChanged;
use Illuminate\Support\Facades\Log;

class LogCompanyStatusChanged
{
    public function handle(CompanyStatusChanged $event): void
    {
        Log::info('Company status changed', [
            'company_id' => $event->company->id,
            'company_name' => $event->company->name,
            'old_status' => $event->oldStatus->value,
            'new_status' => $event->newStatus->value,
            'changed_by' => auth()->id(),
        ]);
    }
}

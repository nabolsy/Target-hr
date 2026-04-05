<?php

namespace App\Observers;

use App\Models\Company;
use Illuminate\Support\Facades\Log;

class CompanyObserver
{
    public function created(Company $company): void
    {
        Log::channel('audit')->info('Company created', [
            'company_id' => $company->id,
            'name' => $company->name,
            'user_id' => auth()->id(),
        ]);
    }

    public function updated(Company $company): void
    {
        Log::channel('audit')->info('Company updated', [
            'company_id' => $company->id,
            'changes' => $company->getChanges(),
            'user_id' => auth()->id(),
        ]);
    }

    public function deleted(Company $company): void
    {
        Log::channel('audit')->info('Company deleted', [
            'company_id' => $company->id,
            'name' => $company->name,
            'user_id' => auth()->id(),
        ]);
    }
}

<?php

namespace App\Providers;

use App\Models\Company;
use App\Models\EmployeeDocument;
use App\Observers\CompanyObserver;
use App\Policies\DocumentPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Company::observe(CompanyObserver::class);

        Gate::policy(EmployeeDocument::class, DocumentPolicy::class);
    }
}

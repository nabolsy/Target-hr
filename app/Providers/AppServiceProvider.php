<?php

namespace App\Providers;

use App\Models\Company;
use App\Models\Employee;
use App\Models\EmployeeDocument;
use App\Observers\CompanyObserver;
use App\Observers\EmployeeObserver;
use App\Policies\DocumentPolicy;
use Illuminate\Support\Facades\Broadcast;
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
        Employee::observe(EmployeeObserver::class);

        Gate::policy(EmployeeDocument::class, DocumentPolicy::class);

        // Mount broadcasting auth endpoint under the SPA's API prefix so
        // Echo can authenticate private-channel subscriptions using the
        // same Sanctum bearer token the rest of the app uses. Without
        // this, the default `/broadcasting/auth` route lives outside
        // the api/v1 prefix and 401s for SPA clients.
        Broadcast::routes([
            'middleware' => ['auth:sanctum'],
            'prefix' => 'api/v1',
        ]);
    }
}

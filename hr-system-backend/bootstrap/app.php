<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
        apiPrefix: 'api/v1',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'tenant' => \App\Http\Middleware\TenantMiddleware::class,
            'role' => \App\Http\Middleware\RoleMiddleware::class,
            'subscription' => \App\Http\Middleware\CheckSubscription::class,
            'plan.limit' => \App\Http\Middleware\CheckPlanLimits::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // SPA-only project: there is no `login` route registered, so
        // when a guest hits an authenticated endpoint Laravel's default
        // redirect-to-named-route fails with "Route [login] not
        // defined". For api/v1/* requests (which is everything in this
        // app), return a 401 JSON instead so the frontend's axios
        // interceptor can route the user to /login itself.
        $exceptions->render(function (\Illuminate\Auth\AuthenticationException $e, $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json(['message' => 'Unauthenticated.'], 401);
            }
        });
    })->create();

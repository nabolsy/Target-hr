<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        if (! auth()->check()) {
            abort(Response::HTTP_UNAUTHORIZED, 'Unauthenticated.');
        }

        $user = auth()->user();

        foreach ($roles as $role) {
            if ($user->hasRole($role)) {
                return $next($request);
            }
        }

        abort(Response::HTTP_FORBIDDEN, 'You do not have the required role.');
    }
}

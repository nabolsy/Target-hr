<?php

namespace App\Http\Middleware;

use App\Exceptions\TenantMismatchException;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TenantMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! auth()->check()) {
            return $next($request);
        }

        $user = auth()->user();

        if (! $user->company_id) {
            throw new TenantMismatchException('User is not associated with any company.');
        }

        // Verify tenant context on route model bindings
        foreach ($request->route()->parameters() as $parameter) {
            if (is_object($parameter) && method_exists($parameter, 'getAttribute')) {
                $companyId = $parameter->getAttribute('company_id');

                if ($companyId !== null && (int) $companyId !== (int) $user->company_id) {
                    throw new TenantMismatchException('Resource does not belong to your company.');
                }
            }
        }

        return $next($request);
    }
}

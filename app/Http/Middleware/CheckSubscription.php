<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckSubscription
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (! $user || ! $user->company_id) {
            return $next($request); // Super admin or no company — skip
        }

        $company = $user->company;
        if (! $company) {
            return response()->json(['message' => 'Company not found.'], 403);
        }

        // Check if company is active
        if (! $company->is_active) {
            return response()->json([
                'message' => 'Your company account has been deactivated. Please contact support.',
                'error_code' => 'COMPANY_DEACTIVATED',
            ], 403);
        }

        // Check subscription status
        $status = $company->subscription_status;

        if ($status === 'expired') {
            return response()->json([
                'message' => 'Your subscription has expired. Please renew to continue.',
                'error_code' => 'SUBSCRIPTION_EXPIRED',
            ], 403);
        }

        if ($status === 'cancelled') {
            return response()->json([
                'message' => 'Your subscription has been cancelled. Please resubscribe.',
                'error_code' => 'SUBSCRIPTION_CANCELLED',
            ], 403);
        }

        // Check trial expiry
        if ($status === 'trial' && $company->trial_ends_at?->isPast()) {
            $company->update(['subscription_status' => 'expired']);
            return response()->json([
                'message' => 'Your free trial has ended. Please subscribe to continue.',
                'error_code' => 'TRIAL_EXPIRED',
            ], 403);
        }

        return $next($request);
    }
}

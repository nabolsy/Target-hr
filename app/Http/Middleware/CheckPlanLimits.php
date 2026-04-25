<?php

namespace App\Http\Middleware;

use App\Models\Employee;
use App\Models\Department;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPlanLimits
{
    public function handle(Request $request, Closure $next, string $resource = ''): Response
    {
        $user = $request->user();
        if (! $user || ! $user->company_id) {
            return $next($request); // Super admin — skip
        }

        // Only check on POST (create) requests
        if (! $request->isMethod('POST')) {
            return $next($request);
        }

        $company = $user->company;
        $plan = $company?->plan;

        if (! $plan) {
            return $next($request); // No plan set — allow (legacy)
        }

        if ($resource === 'employees' && ! $plan->isUnlimited('max_employees')) {
            $count = Employee::where('company_id', $company->id)->count();
            if ($count >= $plan->max_employees) {
                return response()->json([
                    'message' => "Employee limit reached ({$plan->max_employees}). Please upgrade your plan.",
                    'error_code' => 'PLAN_LIMIT_EMPLOYEES',
                    'current' => $count,
                    'limit' => $plan->max_employees,
                ], 403);
            }
        }

        if ($resource === 'departments' && ! $plan->isUnlimited('max_departments')) {
            $count = Department::where('company_id', $company->id)->count();
            if ($count >= $plan->max_departments) {
                return response()->json([
                    'message' => "Department limit reached ({$plan->max_departments}). Please upgrade your plan.",
                    'error_code' => 'PLAN_LIMIT_DEPARTMENTS',
                    'current' => $count,
                    'limit' => $plan->max_departments,
                ], 403);
            }
        }

        return $next($request);
    }
}

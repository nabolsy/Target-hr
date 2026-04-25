<?php

namespace App\Http\Controllers\Api\V1\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Employee;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\Subscription;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $totalCompanies = Company::count();
        $activeCompanies = Company::where('is_active', true)->count();
        $totalEmployees = Employee::count();
        $totalRevenue = Payment::where('status', 'completed')->sum('amount');
        $mrr = Subscription::where('status', 'active')->where('billing_cycle', 'monthly')->sum('price');
        $activeSubscriptions = Subscription::whereIn('status', ['active', 'trial'])->count();
        $trialCompanies = Company::where('subscription_status', 'trial')->count();
        $planDistribution = Plan::withCount('companies')->get()->map(fn ($p) => [
            'name' => $p->name, 'count' => $p->companies_count,
        ]);

        return response()->json([
            'data' => [
                'total_companies' => $totalCompanies,
                'active_companies' => $activeCompanies,
                'total_employees' => $totalEmployees,
                'total_revenue' => round($totalRevenue, 2),
                'mrr' => round($mrr, 2),
                'active_subscriptions' => $activeSubscriptions,
                'trial_companies' => $trialCompanies,
                'plan_distribution' => $planDistribution,
            ],
        ]);
    }
}

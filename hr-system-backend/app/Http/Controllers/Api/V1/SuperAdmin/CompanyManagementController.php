<?php

namespace App\Http\Controllers\Api\V1\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Employee;
use App\Models\Plan;
use App\Models\Subscription;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CompanyManagementController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $companies = Company::with(['plan', 'activeSubscription'])
            ->withCount('employees')
            ->when($request->search, fn ($q, $s) => $q->where('name', 'like', "%{$s}%"))
            ->when($request->status, fn ($q, $s) => $q->where('subscription_status', $s))
            ->orderBy('created_at', 'desc')
            ->paginate($request->integer('per_page', 15));

        return response()->json($companies);
    }

    public function show(Company $company): JsonResponse
    {
        $company->load(['plan', 'activeSubscription', 'subscriptions.plan']);
        $company->loadCount('employees');

        return response()->json(['data' => $company]);
    }

    public function update(Request $request, Company $company): JsonResponse
    {
        $data = $request->validate([
            'name' => 'sometimes|string|max:255',
            'is_active' => 'sometimes|boolean',
            'subscription_status' => 'sometimes|string|in:trial,active,past_due,cancelled,expired',
            'employee_limit' => 'sometimes|integer',
        ]);

        $company->update($data);
        return response()->json(['data' => $company->fresh()->load('plan')]);
    }

    public function stats(Company $company): JsonResponse
    {
        return response()->json([
            'data' => [
                'employee_count' => Employee::where('company_id', $company->id)->count(),
                'active_employees' => Employee::where('company_id', $company->id)->where('status', 'active')->count(),
                'department_count' => $company->employees()->distinct('department_id')->count('department_id'),
                'subscription' => $company->activeSubscription?->load('plan'),
                'plan' => $company->plan,
                'total_payments' => $company->payments()->where('status', 'completed')->sum('amount'),
            ],
        ]);
    }

    public function changePlan(Request $request, Company $company): JsonResponse
    {
        $data = $request->validate([
            'plan_id' => 'required|exists:plans,id',
            'billing_cycle' => 'sometimes|in:monthly,yearly',
        ]);

        $plan = Plan::findOrFail($data['plan_id']);
        $cycle = $data['billing_cycle'] ?? 'monthly';
        $price = $cycle === 'yearly' ? $plan->price_yearly : $plan->price_monthly;

        // Cancel current subscription
        $company->activeSubscription?->update(['status' => 'cancelled', 'cancelled_at' => now()]);

        // Create new subscription
        $subscription = Subscription::create([
            'company_id' => $company->id,
            'plan_id' => $plan->id,
            'status' => 'active',
            'billing_cycle' => $cycle,
            'starts_at' => now(),
            'ends_at' => $cycle === 'yearly' ? now()->addYear() : now()->addMonth(),
            'price' => $price,
        ]);

        $company->update([
            'plan_id' => $plan->id,
            'subscription_status' => 'active',
            'employee_limit' => $plan->max_employees,
        ]);

        return response()->json(['data' => $subscription->load('plan'), 'message' => 'Plan changed successfully.']);
    }
}

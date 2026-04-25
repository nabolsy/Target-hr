<?php

namespace App\Http\Controllers\Api\V1\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PlanController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(['data' => Plan::orderBy('sort_order')->get()]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:plans',
            'description' => 'nullable|string',
            'price_monthly' => 'required|numeric|min:0',
            'price_yearly' => 'required|numeric|min:0',
            'currency' => 'string|max:10',
            'max_employees' => 'required|integer',
            'max_departments' => 'required|integer',
            'max_storage_gb' => 'required|integer',
            'features' => 'nullable|array',
            'is_active' => 'boolean',
            'is_popular' => 'boolean',
            'sort_order' => 'integer',
            'trial_days' => 'integer|min:0',
        ]);

        $plan = Plan::create($data);
        return response()->json(['data' => $plan], 201);
    }

    public function show(Plan $plan): JsonResponse
    {
        return response()->json(['data' => $plan->loadCount(['subscriptions', 'companies'])]);
    }

    public function update(Request $request, Plan $plan): JsonResponse
    {
        $data = $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'price_monthly' => 'sometimes|numeric|min:0',
            'price_yearly' => 'sometimes|numeric|min:0',
            'max_employees' => 'sometimes|integer',
            'max_departments' => 'sometimes|integer',
            'max_storage_gb' => 'sometimes|integer',
            'features' => 'nullable|array',
            'is_active' => 'boolean',
            'is_popular' => 'boolean',
            'sort_order' => 'integer',
            'trial_days' => 'integer|min:0',
        ]);

        $plan->update($data);
        return response()->json(['data' => $plan->fresh()]);
    }

    public function destroy(Plan $plan): JsonResponse
    {
        if ($plan->companies()->count() > 0) {
            return response()->json(['message' => 'Cannot delete plan with active companies.'], 422);
        }
        $plan->delete();
        return response()->json(['message' => 'Plan deleted.']);
    }
}

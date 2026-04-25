<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use Illuminate\Http\JsonResponse;

class PublicPlanController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $plans = Plan::where('is_active', true)
            ->orderBy('sort_order')
            ->get(['id', 'name', 'slug', 'description', 'price_monthly', 'price_yearly', 'currency', 'max_employees', 'max_departments', 'max_storage_gb', 'features', 'is_popular', 'trial_days']);

        return response()->json(['data' => $plans]);
    }
}

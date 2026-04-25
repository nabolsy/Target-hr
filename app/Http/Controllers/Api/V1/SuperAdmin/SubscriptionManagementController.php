<?php

namespace App\Http\Controllers\Api\V1\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Subscription;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SubscriptionManagementController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $subs = Subscription::with(['company', 'plan'])
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->orderBy('created_at', 'desc')
            ->paginate($request->integer('per_page', 15));

        return response()->json($subs);
    }

    public function update(Request $request, Subscription $subscription): JsonResponse
    {
        $data = $request->validate([
            'status' => 'sometimes|in:trial,active,past_due,cancelled,expired',
            'ends_at' => 'sometimes|date',
        ]);

        $subscription->update($data);

        if (isset($data['status'])) {
            $subscription->company->update(['subscription_status' => $data['status']]);
        }

        return response()->json(['data' => $subscription->fresh()->load(['company', 'plan'])]);
    }
}

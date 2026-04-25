<?php

namespace App\Http\Controllers\Api\V1\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentManagementController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $payments = Payment::with(['company', 'subscription.plan'])
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->when($request->company_id, fn ($q, $id) => $q->where('company_id', $id))
            ->orderBy('created_at', 'desc')
            ->paginate($request->integer('per_page', 15));

        return response()->json($payments);
    }

    public function show(Payment $payment): JsonResponse
    {
        return response()->json(['data' => $payment->load(['company', 'subscription.plan'])]);
    }
}

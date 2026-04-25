<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\Subscription;
use App\Services\MoyasarService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BillingController extends Controller
{
    public function __construct(private MoyasarService $moyasar) {}

    /**
     * Get current company's subscription info.
     */
    public function subscription(Request $request): JsonResponse
    {
        $company = $request->user()->company;
        $company->load(['plan', 'activeSubscription']);

        $employeeCount = $company->employees()->count();
        $deptCount = $company->employees()->distinct('department_id')->count('department_id');

        return response()->json([
            'data' => [
                'company' => [
                    'name' => $company->name,
                    'subscription_status' => $company->subscription_status,
                    'trial_ends_at' => $company->trial_ends_at?->toISOString(),
                    'is_active' => $company->is_active,
                ],
                'plan' => $company->plan,
                'subscription' => $company->activeSubscription,
                'usage' => [
                    'employees' => ['current' => $employeeCount, 'limit' => $company->plan?->max_employees ?? 10],
                    'departments' => ['current' => $deptCount, 'limit' => $company->plan?->max_departments ?? 3],
                    'storage_gb' => ['current' => 0, 'limit' => $company->plan?->max_storage_gb ?? 5],
                ],
                'publishable_key' => $this->moyasar->getPublishableKey(),
            ],
        ]);
    }

    /**
     * Initiate a payment for subscription.
     */
    public function initiatePayment(Request $request): JsonResponse
    {
        $data = $request->validate([
            'plan_id' => 'required|exists:plans,id',
            'billing_cycle' => 'required|in:monthly,yearly',
        ]);

        $plan = Plan::findOrFail($data['plan_id']);
        $company = $request->user()->company;
        $price = $data['billing_cycle'] === 'yearly' ? $plan->price_yearly : $plan->price_monthly;
        $amountInHalalas = (int) ($price * 100);

        // Generate invoice number
        $lastInvoice = Invoice::orderBy('id', 'desc')->first();
        $seq = $lastInvoice ? ((int) substr($lastInvoice->invoice_number, -4)) + 1 : 1;
        $invoiceNumber = 'INV-' . date('Y') . '-' . str_pad($seq, 4, '0', STR_PAD_LEFT);

        // Create pending payment record
        $payment = Payment::create([
            'company_id' => $company->id,
            'subscription_id' => $company->activeSubscription?->id ?? 0,
            'amount' => $price,
            'currency' => 'SAR',
            'status' => 'pending',
            'invoice_number' => $invoiceNumber,
            'description' => "HRFlow {$plan->name} Plan - " . ucfirst($data['billing_cycle']),
            'metadata' => ['plan_id' => $plan->id, 'billing_cycle' => $data['billing_cycle']],
        ]);

        return response()->json([
            'data' => [
                'payment_id' => $payment->id,
                'amount' => $amountInHalalas,
                'amount_display' => $price,
                'currency' => 'SAR',
                'description' => $payment->description,
                'publishable_key' => $this->moyasar->getPublishableKey(),
                'callback_url' => $this->moyasar->getCallbackUrl() . "?payment_id={$payment->id}",
                'invoice_number' => $invoiceNumber,
            ],
        ]);
    }

    /**
     * Verify payment after Moyasar callback.
     */
    public function verifyPayment(Request $request, int $id): JsonResponse
    {
        $payment = Payment::findOrFail($id);
        $moyasarId = $request->input('moyasar_id') ?? $request->input('id');

        if ($moyasarId) {
            try {
                $moyasarPayment = $this->moyasar->fetchPayment($moyasarId);
                $payment->update([
                    'moyasar_payment_id' => $moyasarId,
                    'transaction_id' => $moyasarPayment['id'] ?? $moyasarId,
                    'metadata' => array_merge($payment->metadata ?? [], ['moyasar_response' => $moyasarPayment]),
                ]);

                $status = $moyasarPayment['status'] ?? 'failed';
                if ($status === 'paid') {
                    return $this->activatePayment($payment);
                }
            } catch (\Exception $e) {
                // Moyasar API call failed — check if test mode
            }
        }

        // For test mode: allow manual activation
        if ($payment->status === 'pending' && app()->environment('local', 'testing')) {
            return $this->activatePayment($payment);
        }

        return response()->json([
            'data' => [
                'status' => $payment->status,
                'message' => $payment->status === 'completed' ? 'Payment verified successfully.' : 'Payment verification pending.',
            ],
        ]);
    }

    private function activatePayment(Payment $payment): JsonResponse
    {
        return DB::transaction(function () use ($payment) {
            $payment->update(['status' => 'completed', 'paid_at' => now()]);

            $meta = $payment->metadata ?? [];
            $planId = $meta['plan_id'] ?? null;
            $billingCycle = $meta['billing_cycle'] ?? 'monthly';
            $plan = $planId ? Plan::find($planId) : null;
            $company = $payment->company;

            // Cancel old subscription
            $company->activeSubscription?->update(['status' => 'cancelled', 'cancelled_at' => now()]);

            // Create new subscription
            $subscription = Subscription::create([
                'company_id' => $company->id,
                'plan_id' => $plan?->id ?? $company->plan_id,
                'status' => 'active',
                'billing_cycle' => $billingCycle,
                'starts_at' => now(),
                'ends_at' => $billingCycle === 'yearly' ? now()->addYear() : now()->addMonth(),
                'price' => $payment->amount,
            ]);

            $payment->update(['subscription_id' => $subscription->id]);

            // Update company
            $company->update([
                'plan_id' => $plan?->id ?? $company->plan_id,
                'subscription_status' => 'active',
                'employee_limit' => $plan?->max_employees ?? -1,
            ]);

            // Create invoice
            $taxAmount = round($payment->amount * 0.15, 2);
            Invoice::create([
                'company_id' => $company->id,
                'subscription_id' => $subscription->id,
                'payment_id' => $payment->id,
                'invoice_number' => $payment->invoice_number,
                'amount' => $payment->amount,
                'tax_amount' => $taxAmount,
                'total_amount' => $payment->amount + $taxAmount,
                'currency' => 'SAR',
                'status' => 'paid',
                'due_date' => now()->format('Y-m-d'),
                'paid_at' => now(),
                'billing_period_start' => now()->format('Y-m-d'),
                'billing_period_end' => ($billingCycle === 'yearly' ? now()->addYear() : now()->addMonth())->format('Y-m-d'),
            ]);

            return response()->json([
                'data' => [
                    'status' => 'completed',
                    'message' => 'Payment successful! Your subscription is now active.',
                    'subscription' => $subscription->load('plan'),
                ],
            ]);
        });
    }

    /**
     * Get invoices for current company.
     */
    public function invoices(Request $request): JsonResponse
    {
        $invoices = Invoice::where('company_id', $request->user()->company_id)
            ->orderBy('created_at', 'desc')
            ->paginate($request->integer('per_page', 15));

        return response()->json($invoices);
    }

    /**
     * Cancel subscription.
     */
    public function cancelSubscription(Request $request): JsonResponse
    {
        $company = $request->user()->company;
        $subscription = $company->activeSubscription;

        if (! $subscription) {
            return response()->json(['message' => 'No active subscription to cancel.'], 422);
        }

        $subscription->update(['status' => 'cancelled', 'cancelled_at' => now()]);
        $company->update(['subscription_status' => 'cancelled']);

        return response()->json([
            'message' => 'Subscription cancelled. Access will continue until the end of your billing period.',
            'ends_at' => $subscription->ends_at?->toISOString(),
        ]);
    }

    /**
     * Get available plans for upgrade/downgrade.
     */
    public function plans(): JsonResponse
    {
        return response()->json([
            'data' => Plan::where('is_active', true)->orderBy('sort_order')->get(),
        ]);
    }
}

<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\CompanyStatus;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Employee;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpFoundation\Response;

class RegistrationController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $data = $request->validate([
            'company_name' => 'required|string|max:255',
            'admin_name' => 'required|string|max:255',
            'admin_email' => 'required|email|unique:users,email',
            'admin_password' => 'required|string|min:8',
            'plan_id' => 'required|exists:plans,id',
            'industry' => 'nullable|string|max:255',
        ]);

        $plan = Plan::findOrFail($data['plan_id']);

        $result = DB::transaction(function () use ($data, $plan) {
            // Create company
            $company = Company::create([
                'name' => $data['company_name'],
                'email' => $data['admin_email'],
                'status' => CompanyStatus::Active->value,
                'plan_id' => $plan->id,
                'subscription_status' => 'trial',
                'trial_ends_at' => now()->addDays($plan->trial_days),
                'is_active' => true,
                'registered_at' => now(),
                'employee_limit' => $plan->max_employees,
                'industry' => $data['industry'] ?? null,
            ]);

            // Create admin user
            $nameParts = explode(' ', $data['admin_name'], 2);
            $user = User::create([
                'name' => $data['admin_name'],
                'email' => $data['admin_email'],
                'password' => Hash::make($data['admin_password']),
                'company_id' => $company->id,
                'role' => UserRole::CompanyAdmin->value,
                'email_verified_at' => now(),
            ]);

            // Create employee record
            Employee::create([
                'company_id' => $company->id,
                'user_id' => $user->id,
                'employee_id_number' => strtoupper(substr(preg_replace('/[^a-z]/', '', strtolower($data['company_name'])), 0, 3)) . '-001',
                'first_name' => $nameParts[0],
                'last_name' => $nameParts[1] ?? '',
                'email' => $data['admin_email'],
                'employment_type' => 'full_time',
                'status' => 'active',
                'join_date' => now()->format('Y-m-d'),
            ]);

            // Create trial subscription
            $subscription = Subscription::create([
                'company_id' => $company->id,
                'plan_id' => $plan->id,
                'status' => 'trial',
                'billing_cycle' => 'monthly',
                'trial_ends_at' => now()->addDays($plan->trial_days),
                'starts_at' => now(),
                'price' => $plan->price_monthly,
            ]);

            // Create token
            $token = $user->createToken('auth-token')->plainTextToken;

            return compact('company', 'user', 'subscription', 'token');
        });

        return response()->json([
            'message' => 'Registration successful. Your trial has started.',
            'data' => $result['user'],
            'company' => $result['company'],
            'subscription' => $result['subscription'],
            'token' => $result['token'],
        ], Response::HTTP_CREATED);
    }
}

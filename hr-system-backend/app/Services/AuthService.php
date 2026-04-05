<?php

namespace App\Services;

use App\DTOs\LoginDTO;
use App\DTOs\RegisterDTO;
use App\Enums\CompanyStatus;
use App\Enums\SubscriptionPlan;
use App\Enums\UserRole;
use App\Events\UserRegistered;
use App\Models\Company;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthService
{
    /**
     * Register a new company and its admin user.
     *
     * @return array{user: User, company: Company, token: string}
     */
    public function register(RegisterDTO $dto): array
    {
        return DB::transaction(function () use ($dto) {
            $company = Company::create([
                'name' => $dto->companyName,
                'email' => $dto->companyEmail ?? $dto->email,
                'phone' => $dto->phone,
                'address' => $dto->address,
                'city' => $dto->city,
                'state' => $dto->state,
                'country' => $dto->country,
                'postal_code' => $dto->postalCode,
                'website' => $dto->website,
                'industry' => $dto->industry,
                'employee_limit' => $dto->employeeLimit,
                'status' => CompanyStatus::Active,
                'subscription_plan' => SubscriptionPlan::Free,
            ]);

            $user = User::create([
                'name' => $dto->name,
                'email' => $dto->email,
                'password' => $dto->password,
                'company_id' => $company->id,
                'role' => UserRole::CompanyAdmin,
            ]);

            $token = $user->createToken('auth-token')->plainTextToken;

            event(new UserRegistered($user, $company));

            return [
                'user' => $user->load('company'),
                'company' => $company,
                'token' => $token,
            ];
        });
    }

    /**
     * Authenticate a user and create a Sanctum token.
     *
     * @return array{user: User, token: string}
     *
     * @throws ValidationException
     */
    public function login(LoginDTO $dto): array
    {
        $user = User::where('email', $dto->email)->first();

        if (! $user || ! Hash::check($dto->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        $token = $user->createToken('auth-token')->plainTextToken;

        return [
            'user' => $user->load('company'),
            'token' => $token,
        ];
    }

    /**
     * Revoke the current access token.
     */
    public function logout(User $user): void
    {
        $user->currentAccessToken()->delete();
    }
}

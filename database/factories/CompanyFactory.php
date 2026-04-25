<?php

namespace Database\Factories;

use App\Enums\CompanyStatus;
use App\Enums\SubscriptionPlan;
use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

class CompanyFactory extends Factory
{
    protected $model = Company::class;

    public function definition(): array
    {
        return [
            'name' => fake()->company(),
            'email' => fake()->unique()->companyEmail(),
            'phone' => fake()->phoneNumber(),
            'address' => fake()->streetAddress(),
            'city' => fake()->city(),
            'state' => fake()->state(),
            'country' => fake()->country(),
            'postal_code' => fake()->postcode(),
            'website' => fake()->url(),
            'logo' => null,
            'industry' => fake()->randomElement([
                'Technology', 'Healthcare', 'Finance', 'Education',
                'Manufacturing', 'Retail', 'Consulting', 'Construction',
            ]),
            'employee_limit' => fake()->randomElement([10, 50, 200, 1000]),
            'status' => CompanyStatus::Active,
            'subscription_plan' => fake()->randomElement(SubscriptionPlan::cases()),
        ];
    }

    public function active(): static
    {
        return $this->state(fn () => ['status' => CompanyStatus::Active]);
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['status' => CompanyStatus::Inactive]);
    }

    public function suspended(): static
    {
        return $this->state(fn () => ['status' => CompanyStatus::Suspended]);
    }

    public function enterprise(): static
    {
        return $this->state(fn () => [
            'subscription_plan' => SubscriptionPlan::Enterprise,
            'employee_limit' => PHP_INT_MAX,
        ]);
    }
}

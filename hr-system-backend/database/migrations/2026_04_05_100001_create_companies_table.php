<?php

use App\Enums\CompanyStatus;
use App\Enums\SubscriptionPlan;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('phone')->nullable();
            $table->text('address')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('country')->nullable();
            $table->string('postal_code')->nullable();
            $table->string('website')->nullable();
            $table->string('logo')->nullable();
            $table->string('industry')->nullable();
            $table->integer('employee_limit')->default(10);
            $table->string('status')->default(CompanyStatus::Active->value);
            $table->string('subscription_plan')->default(SubscriptionPlan::Free->value);
            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
            $table->index('subscription_plan');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('companies');
    }
};

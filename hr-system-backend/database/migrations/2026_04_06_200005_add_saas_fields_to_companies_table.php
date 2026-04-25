<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->foreignId('plan_id')->nullable()->after('subscription_plan')->constrained('plans')->nullOnDelete();
            $table->string('subscription_status')->default('trial')->after('plan_id');
            $table->timestamp('trial_ends_at')->nullable()->after('subscription_status');
            $table->boolean('is_active')->default(true)->after('trial_ends_at');
            $table->timestamp('registered_at')->nullable()->after('is_active');
            $table->json('settings')->nullable()->after('registered_at');
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropForeign(['plan_id']);
            $table->dropColumn(['plan_id', 'subscription_status', 'trial_ends_at', 'is_active', 'registered_at', 'settings']);
        });
    }
};

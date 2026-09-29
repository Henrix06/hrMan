<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('license_codes', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('plan');
            $table->unsignedInteger('duration_days')->nullable();   // null = perpetual
            $table->unsignedInteger('max_redemptions')->nullable(); // null = unlimited
            $table->unsignedInteger('times_redeemed')->default(0);
            $table->timestamp('expires_at')->nullable();            // validity window for the code itself
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['is_active', 'expires_at']);
        });

        Schema::create('license_redemptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('license_code_id')->constrained('license_codes')->cascadeOnDelete();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('plan');
            $table->unsignedInteger('duration_days')->nullable();
            $table->timestamp('subscription_ends_at')->nullable();
            $table->timestamps();

            // A company can only redeem a given code once.
            $table->unique(['license_code_id', 'tenant_id']);
            $table->index('tenant_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('license_redemptions');
        Schema::dropIfExists('license_codes');
    }
};

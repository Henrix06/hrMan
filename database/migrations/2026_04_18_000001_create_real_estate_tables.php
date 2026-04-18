<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('re_properties', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->unique();
            $table->foreignId('tenant_id')->constrained('users')->cascadeOnDelete();
            $table->string('name');
            $table->string('type');
            $table->text('address');
            $table->string('city');
            $table->string('country')->default('ZA');
            $table->integer('units')->default(1);
            $table->decimal('market_value', 15, 2)->nullable();
            $table->enum('status', ['available', 'partially_let', 'fully_let', 'under_maintenance', 'inactive'])->default('available');
            $table->text('description')->nullable();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'status']);
        });

        Schema::create('re_tenants', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->unique();
            $table->foreignId('company_tenant_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('id_number')->nullable();
            $table->enum('type', ['individual', 'company'])->default('individual');
            $table->text('address')->nullable();
            $table->enum('status', ['active', 'inactive', 'blacklisted'])->default('active');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['company_tenant_id', 'status']);
        });

        Schema::create('re_leases', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->unique();
            $table->foreignId('property_id')->constrained('re_properties')->cascadeOnDelete();
            $table->foreignId('re_tenant_id')->constrained('re_tenants')->cascadeOnDelete();
            $table->foreignId('account_id')->nullable()->constrained('accounts')->nullOnDelete();
            $table->date('start_date');
            $table->date('end_date');
            $table->decimal('monthly_rent', 15, 2);
            $table->decimal('deposit_amount', 15, 2)->default(0);
            $table->boolean('deposit_paid')->default(false);
            $table->integer('payment_due_day')->default(1);
            $table->decimal('penalty_rate', 5, 2)->default(5.00);
            $table->integer('grace_period_days')->default(5);
            $table->enum('status', ['draft', 'active', 'expired', 'terminated', 'renewed'])->default('draft');
            $table->date('terminated_at')->nullable();
            $table->text('termination_reason')->nullable();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['property_id', 'status']);
            $table->index(['re_tenant_id', 'status']);
            $table->index(['status', 'end_date']);
        });

        Schema::create('re_rent_invoices', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->unique();
            $table->foreignId('lease_id')->constrained('re_leases')->cascadeOnDelete();
            $table->date('billing_period');
            $table->date('due_date');
            $table->decimal('rent_amount', 15, 2);
            $table->decimal('penalty_amount', 15, 2)->default(0);
            $table->decimal('total_amount', 15, 2);
            $table->enum('status', ['pending', 'invoiced', 'paid', 'overdue', 'cancelled'])->default('pending');
            $table->date('paid_at')->nullable();
            $table->foreignId('transaction_id')->nullable()->constrained('account_transactions')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['lease_id', 'status']);
            $table->index(['status', 'due_date']);
            $table->unique(['lease_id', 'billing_period']);
        });

        Schema::create('re_maintenance_requests', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->unique();
            $table->foreignId('property_id')->constrained('re_properties')->cascadeOnDelete();
            $table->foreignId('lease_id')->nullable()->constrained('re_leases')->nullOnDelete();
            $table->foreignId('re_tenant_id')->nullable()->constrained('re_tenants')->nullOnDelete();
            $table->string('title');
            $table->text('description');
            $table->enum('category', ['plumbing', 'electrical', 'structural', 'appliance', 'security', 'other'])->default('other');
            $table->enum('priority', ['low', 'medium', 'high', 'urgent'])->default('medium');
            $table->enum('status', ['open', 'in_progress', 'pending_parts', 'resolved', 'closed', 'cancelled'])->default('open');
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('assigned_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->text('resolution_notes')->nullable();
            $table->decimal('repair_cost', 10, 2)->nullable();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->index(['property_id', 'status']);
            $table->index(['status', 'priority']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('re_maintenance_requests');
        Schema::dropIfExists('re_rent_invoices');
        Schema::dropIfExists('re_leases');
        Schema::dropIfExists('re_tenants');
        Schema::dropIfExists('re_properties');
    }
};

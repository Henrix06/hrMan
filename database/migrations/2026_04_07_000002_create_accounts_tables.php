<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Company fund accounts (e.g. Operations Budget, Petty Cash)
        Schema::create('accounts', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique(); // e.g. ACC-OPS-001
            $table->string('type'); // operating, petty_cash, payroll, reserve
            $table->text('description')->nullable();
            $table->decimal('balance', 15, 2)->default(0);
            $table->decimal('budget_limit', 15, 2)->nullable();
            $table->boolean('active')->default(true);
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
        });

        // Immutable double-entry ledger
        Schema::create('account_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->string('reference')->unique(); // TXN-2026-00001
            $table->enum('type', ['credit', 'debit']);
            $table->decimal('amount', 15, 2);
            $table->decimal('balance_after', 15, 2); // snapshot for audit trail
            $table->string('category'); // procurement, overtime, transport, top_up, adjustment
            $table->string('description');
            $table->string('transactable_type')->nullable(); // polymorphic link
            $table->unsignedBigInteger('transactable_id')->nullable();
            $table->string('integrity_hash', 64)->nullable(); // tamper-proof
            $table->timestamp('created_at')->useCurrent();

            $table->index(['account_id', 'created_at']);
            $table->index(['transactable_type', 'transactable_id']);
        });

        // Procurement requests
        Schema::create('procurement_requests', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->unique(); // PRQ-2026-00001
            $table->foreignId('requested_by')->constrained('users')->cascadeOnDelete();
            $table->foreignId('account_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->text('description');
            $table->string('vendor')->nullable();
            $table->decimal('estimated_cost', 15, 2);
            $table->decimal('approved_amount', 15, 2)->nullable();
            $table->decimal('actual_cost', 15, 2)->nullable();
            $table->string('category'); // supplies, equipment, services, software, other
            $table->enum('priority', ['low', 'medium', 'high', 'urgent'])->default('medium');
            $table->enum('status', ['draft', 'pending', 'approved', 'rejected', 'paid', 'cancelled'])->default('draft');
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_notes')->nullable();
            $table->date('required_by')->nullable();
            $table->string('receipt_path')->nullable();
            $table->timestamps();

            $table->index(['requested_by', 'status']);
            $table->index(['status', 'created_at']);
        });

        // Overtime claims
        Schema::create('overtime_claims', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->unique(); // OTC-2026-00001
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('account_id')->nullable()->constrained()->nullOnDelete();
            $table->date('work_date');
            $table->time('start_time');
            $table->time('end_time');
            $table->decimal('hours', 5, 2); // computed
            $table->decimal('hourly_rate', 10, 2);
            $table->decimal('multiplier', 4, 2)->default(1.5); // 1.5x, 2x etc.
            $table->decimal('claim_amount', 10, 2); // hours * rate * multiplier
            $table->decimal('approved_amount', 10, 2)->nullable();
            $table->text('reason');
            $table->enum('status', ['draft', 'pending', 'approved', 'rejected', 'paid'])->default('draft');
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_notes')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
        });

        // Transport / travel refund claims
        Schema::create('transport_claims', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->unique(); // TRC-2026-00001
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('account_id')->nullable()->constrained()->nullOnDelete();
            $table->date('travel_date');
            $table->string('origin');
            $table->string('destination');
            $table->string('purpose');
            $table->string('transport_mode'); // taxi, bus, fuel, flight, other
            $table->decimal('distance_km', 8, 2)->nullable();
            $table->decimal('claim_amount', 10, 2);
            $table->decimal('approved_amount', 10, 2)->nullable();
            $table->text('notes')->nullable();
            $table->string('receipt_path')->nullable();
            $table->enum('status', ['draft', 'pending', 'approved', 'rejected', 'paid'])->default('draft');
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_notes')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transport_claims');
        Schema::dropIfExists('overtime_claims');
        Schema::dropIfExists('procurement_requests');
        Schema::dropIfExists('account_transactions');
        Schema::dropIfExists('accounts');
    }
};

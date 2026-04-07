<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assets', function (Blueprint $table) {
            $table->id();
            $table->string('asset_tag')->unique(); // e.g. AST-2026-0001
            $table->string('name');
            $table->string('category'); // laptop, phone, vehicle, furniture, etc.
            $table->string('brand')->nullable();
            $table->string('model')->nullable();
            $table->string('serial_number')->nullable()->unique();
            $table->date('purchase_date')->nullable();
            $table->decimal('purchase_cost', 10, 2)->nullable();
            $table->enum('condition', ['new', 'good', 'fair', 'poor', 'damaged'])->default('good');
            $table->enum('status', ['available', 'assigned', 'under_maintenance', 'retired'])->default('available');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->index(['status', 'category']);
        });

        Schema::create('asset_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asset_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('assigned_by')->constrained('users')->cascadeOnDelete();
            $table->foreignId('returned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->date('assigned_date');
            $table->date('expected_return_date')->nullable();
            $table->date('actual_return_date')->nullable();
            $table->enum('status', ['active', 'returned', 'overdue'])->default('active');
            $table->text('notes')->nullable();
            $table->text('return_notes')->nullable();
            $table->enum('condition_on_return', ['good', 'fair', 'poor', 'damaged'])->nullable();
            // Tamper-proof integrity hash: SHA-256 of (asset_id|user_id|assigned_by|assigned_date|created_at)
            $table->string('integrity_hash', 64)->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['asset_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_assignments');
        Schema::dropIfExists('assets');
    }
};

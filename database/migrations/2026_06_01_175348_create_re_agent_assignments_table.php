<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('re_agent_assignments', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->unique();
            $table->foreignId('company_tenant_id')->constrained('users')->cascadeOnDelete(); // the HR tenant (company)
            $table->foreignId('unit_id')->constrained('re_property_units')->cascadeOnDelete();
            $table->foreignId('agent_id')->constrained('users')->cascadeOnDelete(); // the agent/manager user
            $table->foreignId('assigned_by')->constrained('users')->cascadeOnDelete(); // manager who assigned
            $table->enum('agent_role', ['agent', 'manager'])->default('agent');
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['company_tenant_id', 'status']);
            $table->index(['unit_id', 'status']);
            $table->index(['agent_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('re_agent_assignments');
    }
};

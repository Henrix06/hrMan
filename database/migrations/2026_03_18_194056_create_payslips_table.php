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
        Schema::create('payslips', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payroll_detail_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('payslip_number')->unique();
            $table->string('pay_period');
            $table->date('issue_date');
            $table->date('pay_date');
            $table->decimal('gross_pay', 10, 2);
            $table->decimal('total_deductions', 10, 2);
            $table->decimal('net_pay', 10, 2);
            $table->string('pdf_path')->nullable();
            $table->boolean('sent_to_employee')->default(false);
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'pay_period']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payslips');
    }
};

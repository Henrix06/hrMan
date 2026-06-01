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
        Schema::create('re_conveyance_documents', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->unique();
            $table->foreignId('company_tenant_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('unit_id')->nullable()->constrained('re_property_units')->nullOnDelete();
            $table->foreignId('property_id')->nullable()->constrained('re_properties')->nullOnDelete();
            $table->string('seller_name');
            $table->string('seller_id_number')->nullable(); // Zimbabwean ID or passport
            $table->string('seller_address')->nullable();
            $table->string('buyer_name');
            $table->string('buyer_id_number')->nullable();
            $table->string('buyer_address')->nullable();
            $table->decimal('purchase_price', 15, 2);
            $table->string('currency')->default('ZWL'); // ZWL, USD, etc.
            $table->date('agreement_date');
            $table->date('transfer_date')->nullable();
            $table->string('deed_number')->nullable(); // Title deed number
            $table->string('stand_number')->nullable(); // Zimbabwean stand number
            $table->string('survey_number')->nullable(); // Survey diagram number
            $table->string('conveyancer_name')->nullable(); // Name of conveyancing attorney
            $table->string('conveyancer_reference')->nullable(); // Attorney's reference
            $table->decimal('transfer_duty', 15, 2)->nullable(); // Zimbabwe transfer duty
            $table->decimal('conveyancing_fee', 15, 2)->nullable();
            $table->decimal('registration_fee', 15, 2)->nullable();
            $table->decimal('other_fees', 15, 2)->default(0);
            $table->text('notes')->nullable();
            $table->enum('status', ['draft', 'pending_approval', 'in_progress', 'transferred', 'cancelled'])->default('draft');
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['company_tenant_id', 'status']);
            $table->index(['deed_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('re_conveyance_documents');
    }
};

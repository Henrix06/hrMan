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
        Schema::create('re_property_units', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->unique();
            $table->foreignId('property_id')->constrained('re_properties')->cascadeOnDelete();
            $table->string('unit_number'); // e.g., 1, 2, 3, A, B, 1A
            $table->string('type')->default('apartment'); // apartment, studio, townhouse, office, retail, etc.
            $table->integer('bedrooms')->default(0);
            $table->integer('bathrooms')->default(0);
            $table->decimal('square_meters', 8, 2)->nullable();
            $table->decimal('rent_amount', 15, 2)->nullable(); // for rental units
            $table->decimal('sale_price', 15, 2)->nullable(); // for sale units
            $table->enum('listing_type', ['rent', 'sale', 'both'])->default('rent');
            $table->enum('status', ['available', 'occupied', 'under_maintenance', 'inactive'])->default('available');
            $table->text('description')->nullable();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['property_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('re_property_units');
    }
};

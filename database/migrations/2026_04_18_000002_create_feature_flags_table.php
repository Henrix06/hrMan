<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('feature_flags', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->default(1)->index();
            $table->string('flag_key');
            $table->boolean('is_enabled')->default(true);
            $table->text('description')->nullable();
            $table->timestamps();
            $table->unique(['tenant_id', 'flag_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('feature_flags');
    }
};

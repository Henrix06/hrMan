<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('module_rules', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->default(1)->index();
            $table->string('subject');
            $table->string('rule_key')->unique();
            $table->json('definition');
            $table->boolean('is_active')->default(true);
            $table->integer('priority')->default(0);
            $table->timestamps();
            $table->index(['tenant_id', 'subject']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('module_rules');
    }
};

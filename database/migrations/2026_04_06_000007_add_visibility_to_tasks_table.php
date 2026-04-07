<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            // 'team' = visible to managers/HR (default), 'private' = creator + their manager only
            $table->enum('visibility', ['team', 'private'])->default('team')->after('priority');
        });
    }

    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropColumn('visibility');
        });
    }
};

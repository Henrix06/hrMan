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
        Schema::table('users', function (Blueprint $table) {
            $table->string('employee_id')->unique()->nullable();
            $table->foreignId('manager_id')->nullable()->constrained('users')->nullOnDelete();
            $table->date('hire_date')->nullable();
            $table->enum('status', ['active', 'inactive', 'terminated', 'probation'])->default('probation');
            $table->decimal('base_salary', 10, 2)->nullable();
            $table->string('department')->nullable();
            $table->string('position')->nullable();
            $table->string('phone')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['manager_id']);
            $table->dropColumn([
                'employee_id',
                'manager_id',
                'hire_date',
                'status',
                'base_salary',
                'department',
                'position',
                'phone',
            ]);
        });
    }
};

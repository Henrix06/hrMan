<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('organizations', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('site_slug')->unique();
            $table->string('plan_key')->default('free');
            $table->unsignedInteger('member_limit')->default(5);
            $table->foreignId('owner_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('organization_id')->nullable()->after('id')->constrained('organizations')->nullOnDelete();
        });

        $existingUsers = DB::table('users')->orderBy('id')->get(['id']);

        if ($existingUsers->isEmpty()) {
            return;
        }

        $timestamp = now();

        $organizationId = DB::table('organizations')->insertGetId([
            'name' => 'Default Organization',
            'slug' => 'default-organization',
            'site_slug' => 'default-organization',
            'plan_key' => 'free',
            'member_limit' => 5,
            'owner_user_id' => $existingUsers->first()->id,
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ]);

        DB::table('users')
            ->whereNull('organization_id')
            ->update(['organization_id' => $organizationId]);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('organization_id');
        });

        Schema::dropIfExists('organizations');
    }
};

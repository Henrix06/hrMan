<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add parent_id to folders for nested subfolders
        Schema::table('folders', function (Blueprint $table) {
            $table->foreignId('parent_id')->nullable()->after('owner_id')
                ->constrained('folders')->nullOnDelete();
        });

        Schema::create('folder_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('folder_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->string('title');
            $table->longText('content')->nullable();
            $table->string('type')->default('document'); // document | note
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('folder_documents');
        Schema::table('folders', function (Blueprint $table) {
            $table->dropForeign(['parent_id']);
            $table->dropColumn('parent_id');
        });
    }
};

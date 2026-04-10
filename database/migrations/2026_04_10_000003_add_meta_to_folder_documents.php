<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('folder_documents', function (Blueprint $table) {
            // JSON blob for spreadsheet cell data: { cells: { "A1": { v: "...", bold: true, ... } } }
            $table->json('meta')->nullable()->after('content');
        });
    }

    public function down(): void
    {
        Schema::table('folder_documents', function (Blueprint $table) {
            $table->dropColumn('meta');
        });
    }
};

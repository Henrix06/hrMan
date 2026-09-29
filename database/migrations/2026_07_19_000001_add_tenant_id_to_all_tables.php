<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $tables = [
            'departments',
            'tasks',
            'task_comments',
            'announcements',
            'hr_notifications',
            'leave_types',
            'leave_requests',
            'leave_balances',
            'payroll_details',
            'payslips',
            'assets',
            'asset_assignments',
            'accounts',
            'account_transactions',
            'procurement_requests',
            'overtime_claims',
            'transport_claims',
            'folders',
            'folder_shares',
            'folder_documents',
            'audit_logs',
            'chat_conversations',
            'chat_messages',
        ];

        foreach ($tables as $table) {
            Schema::table($table, function (Blueprint $table) {
                $table->unsignedBigInteger('tenant_id')->nullable()->after('id');
                $table->foreign('tenant_id')->references('id')->on('tenants')->nullOnDelete();
                $table->index('tenant_id');
            });
        }
    }

    public function down(): void
    {
        $tables = [
            'departments',
            'tasks',
            'task_comments',
            'announcements',
            'hr_notifications',
            'leave_types',
            'leave_requests',
            'leave_balances',
            'payroll_details',
            'payslips',
            'assets',
            'asset_assignments',
            'accounts',
            'account_transactions',
            'procurement_requests',
            'overtime_claims',
            'transport_claims',
            'folders',
            'folder_shares',
            'folder_documents',
            'audit_logs',
            'chat_conversations',
            'chat_messages',
        ];

        foreach ($tables as $table) {
            Schema::table($table, function (Blueprint $table) {
                $table->dropForeign(['tenant_id']);
                $table->dropIndex(['tenant_id']);
                $table->dropColumn('tenant_id');
            });
        }
    }
};

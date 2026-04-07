<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // tasks: composite indexes for the most common query patterns
        Schema::table('tasks', function (Blueprint $table) {
            $table->index(['assigned_to', 'status'], 'tasks_assigned_status');
            $table->index(['created_by', 'status'], 'tasks_created_status');
            $table->index(['status', 'due_date'], 'tasks_status_due');
            $table->index('priority', 'tasks_priority');
            $table->index('visibility', 'tasks_visibility');
        });

        // chat_messages: unread lookup + conversation browsing
        Schema::table('chat_messages', function (Blueprint $table) {
            $table->index(['conversation_id', 'read_at'], 'chat_messages_conv_read');
            $table->index(['conversation_id', 'created_at'], 'chat_messages_conv_created');
            $table->index('sender_id', 'chat_messages_sender');
        });

        // chat_conversations: ordering by last activity
        Schema::table('chat_conversations', function (Blueprint $table) {
            $table->index('last_message_at', 'chat_conv_last_message');
        });

        // hr_notifications: unread count per user (called on every page load)
        Schema::table('hr_notifications', function (Blueprint $table) {
            $table->index(['user_id', 'read_at'], 'hr_notif_user_read');
            $table->index('type', 'hr_notif_type');
        });

        // audit_logs: admin viewer filters
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->index('user_id', 'audit_user');
            $table->index('action', 'audit_action');
            $table->index('created_at', 'audit_created');
        });

        // announcements: published feed
        Schema::table('announcements', function (Blueprint $table) {
            $table->index(['published_at', 'pinned'], 'announcements_feed');
            $table->index('audience', 'announcements_audience');
        });

        // leave_requests: approval queue + user history
        Schema::table('leave_requests', function (Blueprint $table) {
            $table->index(['user_id', 'status'], 'leave_user_status');
            $table->index(['status', 'created_at'], 'leave_status_created');
        });

        // leave_balances: per-user per-year lookup
        Schema::table('leave_balances', function (Blueprint $table) {
            $table->index(['user_id', 'year'], 'leave_balance_user_year');
        });

        // payslips: user payslip history
        Schema::table('payslips', function (Blueprint $table) {
            $table->index(['user_id', 'created_at'], 'payslips_user_created');
        });

        // payroll_details: period lookup
        Schema::table('payroll_details', function (Blueprint $table) {
            $table->index(['user_id', 'pay_period'], 'payroll_user_period');
            $table->index('status', 'payroll_status');
        });
    }

    public function down(): void
    {
        Schema::table('tasks', fn($t) => $t->dropIndex(['tasks_assigned_status', 'tasks_created_status', 'tasks_status_due', 'tasks_priority', 'tasks_visibility']));
        Schema::table('chat_messages', fn($t) => $t->dropIndex(['chat_messages_conv_read', 'chat_messages_conv_created', 'chat_messages_sender']));
        Schema::table('chat_conversations', fn($t) => $t->dropIndex(['chat_conv_last_message']));
        Schema::table('hr_notifications', fn($t) => $t->dropIndex(['hr_notif_user_read', 'hr_notif_type']));
        Schema::table('audit_logs', fn($t) => $t->dropIndex(['audit_user', 'audit_action', 'audit_created']));
        Schema::table('announcements', fn($t) => $t->dropIndex(['announcements_feed', 'announcements_audience']));
        Schema::table('leave_requests', fn($t) => $t->dropIndex(['leave_user_status', 'leave_status_created']));
        Schema::table('leave_balances', fn($t) => $t->dropIndex(['leave_balance_user_year']));
        Schema::table('payslips', fn($t) => $t->dropIndex(['payslips_user_created']));
        Schema::table('payroll_details', fn($t) => $t->dropIndex(['payroll_user_period', 'payroll_status']));
    }
};

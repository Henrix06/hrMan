<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // users: common lookup patterns
        Schema::table('users', function (Blueprint $table) {
            $table->index('status', 'users_status');
            $table->index('department_id', 'users_department_id');
            $table->index('manager_id', 'users_manager_id');
        });

        // asset_assignments: active assets per user
        if (Schema::hasTable('asset_assignments')) {
            Schema::table('asset_assignments', function (Blueprint $table) {
                $table->index(['user_id', 'status'], 'asset_assign_user_status');
                $table->index('asset_id', 'asset_assign_asset_id');
            });
        }

        // assets: status + category lookups
        if (Schema::hasTable('assets')) {
            Schema::table('assets', function (Blueprint $table) {
                $table->index(['status', 'category'], 'assets_status_category');
            });
        }

        // account_transactions: account + type lookups
        if (Schema::hasTable('account_transactions')) {
            Schema::table('account_transactions', function (Blueprint $table) {
                $table->index(['account_id', 'type'], 'acct_txn_account_type');
                $table->index('created_at', 'acct_txn_created');
            });
        }

        // real estate: lease lookups
        if (Schema::hasTable('leases')) {
            Schema::table('leases', function (Blueprint $table) {
                $table->index(['property_id', 'status'], 'leases_property_status');
                $table->index(['tenant_id', 'status'], 'leases_tenant_status');
                $table->index('end_date', 'leases_end_date');
            });
        }

        // rent_invoices: unpaid invoice lookups
        if (Schema::hasTable('rent_invoices')) {
            Schema::table('rent_invoices', function (Blueprint $table) {
                $table->index(['lease_id', 'status'], 'rent_inv_lease_status');
                $table->index('due_date', 'rent_inv_due_date');
            });
        }

        // maintenance_requests: open requests per property
        if (Schema::hasTable('maintenance_requests')) {
            Schema::table('maintenance_requests', function (Blueprint $table) {
                $table->index(['property_id', 'status'], 'maint_property_status');
                $table->index('priority', 'maint_priority');
            });
        }
    }

    public function down(): void
    {
        Schema::table('users', fn($t) => $t->dropIndex(['users_status', 'users_department_id', 'users_manager_id']));

        if (Schema::hasTable('asset_assignments')) {
            Schema::table('asset_assignments', fn($t) => $t->dropIndex(['asset_assign_user_status', 'asset_assign_asset_id']));
        }
        if (Schema::hasTable('assets')) {
            Schema::table('assets', fn($t) => $t->dropIndex(['assets_status_category']));
        }
        if (Schema::hasTable('account_transactions')) {
            Schema::table('account_transactions', fn($t) => $t->dropIndex(['acct_txn_account_type', 'acct_txn_created']));
        }
        if (Schema::hasTable('leases')) {
            Schema::table('leases', fn($t) => $t->dropIndex(['leases_property_status', 'leases_tenant_status', 'leases_end_date']));
        }
        if (Schema::hasTable('rent_invoices')) {
            Schema::table('rent_invoices', fn($t) => $t->dropIndex(['rent_inv_lease_status', 'rent_inv_due_date']));
        }
        if (Schema::hasTable('maintenance_requests')) {
            Schema::table('maintenance_requests', fn($t) => $t->dropIndex(['maint_property_status', 'maint_priority']));
        }
    }
};

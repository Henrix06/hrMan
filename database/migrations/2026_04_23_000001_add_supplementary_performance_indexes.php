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
    }
};

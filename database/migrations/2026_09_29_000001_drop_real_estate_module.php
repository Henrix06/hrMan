<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Modules and permissions that belonged to the removed Real Estate module.
     *
     * @var list<string>
     */
    private array $moduleKeys = [
        'real_estate',
        'real_estate_accountant',
    ];

    /**
     * Real Estate tables in child-first order so foreign keys resolve cleanly.
     *
     * @var list<string>
     */
    private array $tables = [
        're_conveyance_documents',
        're_agent_assignments',
        're_property_units',
        're_maintenance_requests',
        're_rent_invoices',
        're_leases',
        're_tenants',
        're_properties',
    ];

    public function up(): void
    {
        foreach ($this->tables as $table) {
            Schema::dropIfExists($table);
        }

        if (Schema::hasTable('tenant_modules')) {
            DB::table('tenant_modules')->whereIn('module_key', $this->moduleKeys)->delete();
        }

        if (Schema::hasTable('user_module_permissions')) {
            DB::table('user_module_permissions')->whereIn('module_key', $this->moduleKeys)->delete();
        }

        if (Schema::hasTable('permissions')) {
            $permissionIds = DB::table('permissions')
                ->where('name', 'like', 'real_estate.%')
                ->pluck('id');

            if ($permissionIds->isNotEmpty()) {
                if (Schema::hasTable('role_has_permissions')) {
                    DB::table('role_has_permissions')->whereIn('permission_id', $permissionIds)->delete();
                }

                if (Schema::hasTable('model_has_permissions')) {
                    DB::table('model_has_permissions')->whereIn('permission_id', $permissionIds)->delete();
                }

                DB::table('permissions')->whereIn('id', $permissionIds)->delete();
            }
        }

        if (Schema::hasTable('module_rules')) {
            DB::table('module_rules')
                ->where(function ($query) {
                    $query->whereIn('subject', $this->moduleKeys)
                        ->orWhere('rule_key', 'like', 'real_estate%');
                })
                ->delete();
        }
    }

    public function down(): void
    {
        // The Real Estate module was removed permanently and is not restorable.
    }
};

<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create permissions
        $userPermissions = [
            'view profile',
            'edit profile',
            'apply leave',
            'view own payslips',
        ];

        $managerPermissions = [
            'view team',
            'approve leave',
            'reject leave',
            'view team payslips',
            'create employee',
            'edit employee',
        ];

        $hrPermissions = [
            'view all employees',
            'create employee',
            'edit employee',
            'delete employee',
            'manage leave types',
            'approve leave',
            'reject leave',
            'run payroll',
            'view all payslips',
            'edit payroll',
            'generate reports',
        ];

        $adminPermissions = [
            'manage roles',
            'manage permissions',
            'manage settings',
            'view audit logs',
            'delete records',
        ];

        $assetPermissions = [
            'view assets',
            'assign assets',
            'manage assets',
        ];

        $accountPermissions = [
            'view accounts',
            'manage accounts',
            'approve procurement',
            'approve claims',
        ];

        $realEstatePermissions = [
            'real_estate.view',
            'real_estate.manage_leases',
            'real_estate.manage_maintenance',
            'real_estate.manage_invoices',
        ];

        // Create all permissions
        $allPermissions = array_merge($userPermissions, $managerPermissions, $hrPermissions, $adminPermissions, $assetPermissions, $accountPermissions, $realEstatePermissions);
        
        foreach ($allPermissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Create roles and assign permissions
        $employeeRole = Role::firstOrCreate(['name' => 'Employee']);
        $employeeRole->syncPermissions(array_merge($userPermissions, ['view assets', 'view accounts', 'real_estate.view']));

        $managerRole = Role::firstOrCreate(['name' => 'Manager']);
        $managerRole->syncPermissions(array_merge($userPermissions, $managerPermissions, ['view assets', 'assign assets', 'view accounts', 'approve claims', 'real_estate.view']));

        $hrAdminRole = Role::firstOrCreate(['name' => 'HR Admin']);
        $hrAdminRole->syncPermissions(array_merge($userPermissions, $managerPermissions, $hrPermissions, $assetPermissions, $accountPermissions, $realEstatePermissions));

        $superAdminRole = Role::firstOrCreate(['name' => 'Super Admin']);
        $superAdminRole->syncPermissions(array_merge($userPermissions, $managerPermissions, $hrPermissions, $adminPermissions, $assetPermissions, $accountPermissions, $realEstatePermissions));
    }
}

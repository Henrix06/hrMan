<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

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

        // Everyone who sells can log orders and arrange their transport;
        // confirming orders and running the transport desk is a management job.
        $salesStaffPermissions = [
            'view sales',
            'create sales order',
            'request transport',
        ];

        $salesPermissions = array_merge($salesStaffPermissions, [
            'manage sales',
            'manage transport',
        ]);

        // Create all permissions
        $allPermissions = array_merge($userPermissions, $managerPermissions, $hrPermissions, $adminPermissions, $assetPermissions, $accountPermissions, $salesPermissions);

        foreach ($allPermissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Create roles and assign permissions
        $employeeRole = Role::firstOrCreate(['name' => 'Employee']);
        $employeeRole->syncPermissions(array_merge($userPermissions, ['view assets', 'view accounts'], $salesStaffPermissions));

        $managerRole = Role::firstOrCreate(['name' => 'Manager']);
        $managerRole->syncPermissions(array_merge($userPermissions, $managerPermissions, ['view assets', 'assign assets', 'view accounts', 'approve claims'], $salesStaffPermissions, ['manage sales']));

        $hrAdminRole = Role::firstOrCreate(['name' => 'HR Admin']);
        $hrAdminRole->syncPermissions(array_merge($userPermissions, $managerPermissions, $hrPermissions, $assetPermissions, $accountPermissions, $salesPermissions));

        $superAdminRole = Role::firstOrCreate(['name' => 'Super Admin']);
        $superAdminRole->syncPermissions(array_merge($userPermissions, $managerPermissions, $hrPermissions, $adminPermissions, $assetPermissions, $accountPermissions, $salesPermissions));
    }
}

<?php

namespace App\Services;

use App\Mail\WelcomeEmail;
use App\Models\Tenant;
use App\Models\TenantModule;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

class TenantProvisioner
{
    /**
     * Provision a brand-new tenant with a super-admin user, or join an existing one.
     */
    public function provision(array $data): array
    {
        // If organisation_id is provided, join the existing tenant
        if (!empty($data['organisation_id'])) {
            return $this->joinExistingTenant($data);
        }

        // Otherwise, create a new tenant
        return $this->createNewTenant($data);
    }

    private function joinExistingTenant(array $data): array
    {
        return DB::transaction(function () use ($data) {
            $tenant = Tenant::findOrFail($data['organisation_id']);

            // Get the next employee ID for this tenant
            $lastEmployee = User::where('tenant_id', $tenant->id)
                ->orderByDesc('employee_id')
                ->value('employee_id');

            $nextNumber = 1;
            if ($lastEmployee && preg_match('/EMP(\d+)/', $lastEmployee, $matches)) {
                $nextNumber = (int) $matches[1] + 1;
            }
            $employeeId = 'EMP' . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);

            // Create the user assigned to the existing tenant
            $user = User::create([
                'name'        => $data['name'],
                'email'       => $data['email'],
                'password'    => Hash::make($data['password']),
                'tenant_id'   => $tenant->id,
                'employee_id' => $employeeId,
                'status'      => 'active',
                'hire_date'   => now()->toDateString(),
                'position'    => 'Employee',
            ]);

            // Assign the Employee role
            $role = Role::firstOrCreate(['name' => 'Employee', 'guard_name' => 'web']);
            $user->assignRole($role);

            // Fire the Registered event so email verification mail is sent
            event(new Registered($user));

            // Send welcome email
            Mail::to($user->email)->send(new WelcomeEmail($user, $tenant));

            return compact('tenant', 'user');
        });
    }

    private function createNewTenant(array $data): array
    {
        return DB::transaction(function () use ($data) {
            // Auto-generate a subdomain slug from company name
            $subdomain = Str::slug($data['company_name'], '-');
            $subdomain = Str::limit($subdomain, 30, '');
            $original = $subdomain;
            $attempt = 0;
            while (Tenant::where('subdomain', $subdomain)->exists()) {
                $attempt++;
                $subdomain = $original . '-' . $attempt;
            }

            // 1. Create the tenant record
            $tenant = Tenant::create([
                'name'           => $data['company_name'],
                'subdomain'      => $subdomain,
                'plan'           => $data['plan'],
                'billing_cycle'  => $data['billing_cycle'],
                'status'         => 'active',
                'trial_ends_at'  => now()->addDays(14),
                'payment_method' => $data['payment_method'],
            ]);

            // 2. Create the super-admin user
            $user = User::create([
                'name'        => $data['name'],
                'email'       => $data['email'],
                'password'    => Hash::make($data['password']),
                'tenant_id'   => $tenant->id,
                'employee_id' => 'EMP001',
                'status'      => 'active',
                'hire_date'   => now()->toDateString(),
                'position'    => 'Administrator',
            ]);

            // 3. Assign the Super Admin role (create it if it doesn't exist yet)
            $role = Role::firstOrCreate(['name' => 'Super Admin', 'guard_name' => 'web']);
            $user->assignRole($role);

            // 4. Seed the modules allowed by the chosen plan
            $planModules = config("pricing.plans.{$data['plan']}.modules", []);
            foreach ($planModules as $moduleKey) {
                TenantModule::create([
                    'tenant_id'  => $tenant->id,
                    'module_key' => $moduleKey,
                    'is_enabled' => true,
                ]);
            }

            // 5. Fire the Registered event so email verification mail is sent
            event(new Registered($user));

            // 6. Send welcome email
            Mail::to($user->email)->send(new WelcomeEmail($user, $tenant));

            return compact('tenant', 'user');
        });
    }
}

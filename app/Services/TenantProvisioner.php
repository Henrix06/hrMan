<?php

namespace App\Services;

use App\Models\Tenant;
use App\Models\TenantModule;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

class TenantProvisioner
{
    /**
     * Provision a brand-new tenant with a super-admin user.
     *
     * @param  array{
     *   name: string,
     *   email: string,
     *   password: string,
     *   company_name: string,
     *   subdomain: string,
     *   plan: string,
     *   billing_cycle: string,
     *   payment_method: array,
     * } $data
     */
    public function provision(array $data): array
    {
        return DB::transaction(function () use ($data) {
            // 1. Create the tenant record
            $tenant = Tenant::create([
                'name'          => $data['company_name'],
                'subdomain'     => $data['subdomain'],
                'plan'          => $data['plan'],
                'billing_cycle' => $data['billing_cycle'],
                'status'        => 'active',
                'trial_ends_at' => now()->addDays(14),
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

            return compact('tenant', 'user');
        });
    }

    /**
     * Generate a URL-safe subdomain slug from a company name,
     * appending a random suffix if the slug is already taken.
     */
    public function suggestSubdomain(string $companyName): string
    {
        $base = Str::slug($companyName, '-');
        $base = Str::limit($base, 30, '');

        $candidate = $base;
        $attempt   = 0;

        while (Tenant::where('subdomain', $candidate)->exists()) {
            $attempt++;
            $candidate = $base . '-' . $attempt;
        }

        return $candidate;
    }

    /**
     * Check whether a subdomain is available.
     */
    public function isSubdomainAvailable(string $subdomain): bool
    {
        return ! Tenant::where('subdomain', $subdomain)->exists();
    }
}

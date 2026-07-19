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
     * Provision a brand-new tenant with a super-admin user.
     */
    public function provision(array $data): array
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

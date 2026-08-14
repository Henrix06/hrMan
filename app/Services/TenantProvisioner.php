<?php

namespace App\Services;

use App\Mail\WelcomeEmail;
use App\Models\Tenant;
use App\Models\TenantModule;
use App\Models\User;
use App\Support\Employees\EmployeeIdGenerator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

class TenantProvisioner
{
    public function provision(array $data): array
    {
        return DB::transaction(function () use ($data) {
            $subdomain = Str::slug($data['company_name'], '-');
            $subdomain = Str::limit($subdomain, 30, '');
            $original = $subdomain;
            $attempt = 0;
            while (Tenant::where('subdomain', $subdomain)->exists()) {
                $attempt++;
                $subdomain = $original . '-' . $attempt;
            }

            $tenant = Tenant::create([
                'name'           => $data['company_name'],
                'subdomain'      => $subdomain,
                'plan'           => $data['plan'],
                'billing_cycle'  => $data['billing_cycle'],
                'status'         => 'active',
                'trial_ends_at'  => now()->addDays(14),
                'payment_method' => $data['payment_method'],
            ]);

            $user = User::create([
                'name'        => $data['name'],
                'email'       => $data['email'],
                'password'    => Hash::make($data['password']),
                'tenant_id'   => $tenant->id,
                'employee_id' => app(EmployeeIdGenerator::class)->next(),
                'status'      => 'active',
                'hire_date'   => now()->toDateString(),
                'position'    => 'Administrator',
                'email_verified_at' => now(),
            ]);

            $role = Role::firstOrCreate(['name' => 'Super Admin', 'guard_name' => 'web']);
            $user->assignRole($role);

            $planModules = config("pricing.plans.{$data['plan']}.modules", []);
            foreach ($planModules as $moduleKey) {
                TenantModule::create([
                    'tenant_id'  => $tenant->id,
                    'module_key' => $moduleKey,
                    'is_enabled' => true,
                ]);
            }

            Mail::to($user->email)->send(new WelcomeEmail($user, $tenant));

            return compact('tenant', 'user');
        });
    }
}

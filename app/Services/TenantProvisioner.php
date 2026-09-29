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
        $result = DB::transaction(function () use ($data) {
            $tenant = Tenant::create([
                'name' => $data['company_name'],
                'subdomain' => $this->uniqueSubdomain($data['company_name']),
                'plan' => $data['plan'],
                'billing_cycle' => $data['billing_cycle'] ?? 'monthly',
                'status' => 'active',
                'trial_ends_at' => now()->addDays((int) config('pricing.trial_days', 14)),
                'payment_method' => $data['payment_method'] ?? ['type' => 'none'],
            ]);

            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'tenant_id' => $tenant->id,
                'employee_id' => app(EmployeeIdGenerator::class)->next(),
                'status' => 'active',
                'hire_date' => now()->toDateString(),
                'position' => 'Administrator',
                'email_verified_at' => now(),
            ]);

            $role = Role::firstOrCreate(['name' => 'Super Admin', 'guard_name' => 'web']);
            $user->assignRole($role);

            foreach (config("pricing.plans.{$data['plan']}.modules", []) as $moduleKey) {
                TenantModule::create([
                    'tenant_id' => $tenant->id,
                    'module_key' => $moduleKey,
                    'is_enabled' => true,
                ]);
            }

            return compact('tenant', 'user');
        });

        // The workspace already exists at this point, so a mail failure must
        // never surface as a signup failure.
        try {
            Mail::to($result['user']->email)->send(new WelcomeEmail($result['user'], $result['tenant']));
        } catch (\Throwable $e) {
            report($e);
        }

        return $result;
    }

    /**
     * Build a slugged, unique subdomain from the company name.
     */
    private function uniqueSubdomain(string $companyName): string
    {
        $subdomain = Str::limit(Str::slug($companyName, '-'), 30, '');

        if ($subdomain === '') {
            $subdomain = 'workspace';
        }

        $original = $subdomain;
        $attempt = 0;

        while (Tenant::where('subdomain', $subdomain)->exists()) {
            $attempt++;
            $subdomain = $original.'-'.$attempt;
        }

        return $subdomain;
    }
}

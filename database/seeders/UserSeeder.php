<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $accounts = collect(config('demo_accounts'));
        $seededUsers = [];

        foreach ($accounts as $account) {
            $managerId = null;

            if (!empty($account['manager_email']) && isset($seededUsers[$account['manager_email']])) {
                $managerId = $seededUsers[$account['manager_email']]->id;
            }

            $user = User::updateOrCreate(
                ['email' => $account['email']],
                [
                    'name' => $account['name'],
                    'password' => Hash::make($account['password']),
                    'employee_id' => $account['employee_id'],
                    'status' => $account['status'],
                    'department' => $account['department'],
                    'position' => $account['position'],
                    'hire_date' => $this->resolveHireDate($account['hire_offset'] ?? []),
                    'base_salary' => $account['base_salary'],
                    'manager_id' => $managerId,
                    'email_verified_at' => now(),
                ]
            );

            $user->syncRoles([$account['role']]);
            $seededUsers[$account['email']] = $user;
        }
    }

    private function resolveHireDate(array $offset): \Illuminate\Support\Carbon
    {
        $date = now();

        if (isset($offset['years'])) {
            $date = $date->subYears($offset['years']);
        }

        if (isset($offset['months'])) {
            $date = $date->subMonths($offset['months']);
        }

        if (isset($offset['days'])) {
            $date = $date->subDays($offset['days']);
        }

        return $date;
    }
}

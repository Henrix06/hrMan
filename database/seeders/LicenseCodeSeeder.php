<?php

namespace Database\Seeders;

use App\Models\LicenseCode;
use Illuminate\Database\Seeder;

class LicenseCodeSeeder extends Seeder
{
    /**
     * Demo codes you can type into the Licence page to unlock a plan.
     * Generate real ones with `php artisan license:generate`.
     */
    public function run(): void
    {
        $codes = [
            [
                'code' => 'UMSIZI-DEMO-PRO-2026',
                'plan' => 'professional',
                'duration_days' => 365,
                'max_redemptions' => 25,
                'is_active' => true,
                'notes' => 'Demo code — unlocks the Professional plan for 365 days.',
            ],
            [
                'code' => 'UMSIZI-DEMO-TEAM-2026',
                'plan' => 'starter',
                'duration_days' => 180,
                'max_redemptions' => 25,
                'is_active' => true,
                'notes' => 'Demo code — unlocks the Starter plan for 180 days.',
            ],
        ];

        foreach ($codes as $code) {
            LicenseCode::updateOrCreate(['code' => $code['code']], $code);
        }
    }
}

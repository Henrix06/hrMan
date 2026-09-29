<?php

namespace App\Console\Commands;

use App\Models\LicenseCode;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Auth;

class GenerateLicenseCodesCommand extends Command
{
    protected $signature = 'license:generate
                            {--plan=professional : Plan key from config/pricing.php}
                            {--days=365 : Access duration in days (0 for a perpetual licence)}
                            {--count=1 : How many codes to generate}
                            {--max-redemptions=1 : Redemptions allowed per code (0 for unlimited)}
                            {--expires-in= : Days before the code itself stops working}
                            {--code= : Use an explicit code instead of a random one}
                            {--notes= : Optional note stored with each code}';

    protected $description = 'Generate licence codes that companies can redeem for a plan';

    public function handle(): int
    {
        $plan = (string) $this->option('plan');
        $plans = config('pricing.plans', []);

        if (! array_key_exists($plan, $plans)) {
            $this->error("Unknown plan '{$plan}'. Available: ".implode(', ', array_keys($plans)));

            return self::FAILURE;
        }

        $count = max(1, (int) $this->option('count'));
        $days = (int) $this->option('days');
        $maxRedemptions = (int) $this->option('max-redemptions');
        $expiresIn = $this->option('expires-in');

        $rows = [];

        for ($i = 0; $i < $count; $i++) {
            $code = $this->option('code')
                ? LicenseCode::normalize((string) $this->option('code'))
                : LicenseCode::generate();

            if (LicenseCode::where('code', $code)->exists()) {
                $this->warn("Skipped {$code} — a code with that value already exists.");

                continue;
            }

            $license = LicenseCode::create([
                'code' => $code,
                'plan' => $plan,
                'duration_days' => $days > 0 ? $days : null,
                'max_redemptions' => $maxRedemptions > 0 ? $maxRedemptions : null,
                'expires_at' => $expiresIn !== null ? now()->addDays((int) $expiresIn) : null,
                'is_active' => true,
                'notes' => $this->option('notes'),
                'created_by' => Auth::id(),
            ]);

            $rows[] = [
                $license->code,
                $license->plan,
                $license->duration_days ?? 'perpetual',
                $license->max_redemptions ?? 'unlimited',
            ];
        }

        if ($rows === []) {
            $this->error('No licence codes were generated.');

            return self::FAILURE;
        }

        $this->table(['Code', 'Plan', 'Duration (days)', 'Max redemptions'], $rows);
        $this->info(count($rows).' licence code(s) generated.');

        return self::SUCCESS;
    }
}

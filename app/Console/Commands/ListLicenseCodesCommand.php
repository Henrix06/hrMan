<?php

namespace App\Console\Commands;

use App\Models\LicenseCode;
use Illuminate\Console\Command;

class ListLicenseCodesCommand extends Command
{
    protected $signature = 'license:list
                            {--plan= : Only show codes for this plan}
                            {--code= : Look up a single code}';

    protected $description = 'List licence codes and how many times each has been redeemed';

    public function handle(): int
    {
        $query = LicenseCode::query()->withCount('redemptions')->orderByDesc('created_at');

        if ($plan = $this->option('plan')) {
            $query->where('plan', $plan);
        }

        if ($code = $this->option('code')) {
            $query->where('code', LicenseCode::normalize((string) $code));
        }

        $codes = $query->get();

        if ($codes->isEmpty()) {
            $this->warn('No licence codes found.');

            return self::SUCCESS;
        }

        $this->table(
            ['Code', 'Plan', 'Duration (days)', 'Used / Max', 'Code expires', 'Active'],
            $codes->map(fn (LicenseCode $license) => [
                $license->code,
                $license->plan,
                $license->duration_days ?? 'perpetual',
                $license->times_redeemed.' / '.($license->max_redemptions ?? '∞'),
                $license->expires_at?->format('Y-m-d') ?? '—',
                $license->is_active ? 'yes' : 'no',
            ])->all()
        );

        return self::SUCCESS;
    }
}

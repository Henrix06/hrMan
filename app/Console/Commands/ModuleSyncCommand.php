<?php

namespace App\Console\Commands;

use App\Models\TenantModule;
use Illuminate\Console\Command;

class ModuleSyncCommand extends Command
{
    protected $signature = 'modules:sync
                            {--tenant=1 : Tenant ID to sync}
                            {--plan= : Override plan key (defaults to app.plan config)}';

    protected $description = 'Seed tenant_modules from the pricing plan configuration';

    public function handle(): int
    {
        $tenantId = (int) $this->option('tenant');
        $plan     = $this->option('plan') ?: config('app.plan', 'free');

        $allModules  = array_keys(config('pricing.modules', []));
        $planModules = config("pricing.plans.{$plan}.modules", []);

        if (empty($allModules)) {
            $this->error('No modules found in config/pricing.php.');
            return self::FAILURE;
        }

        $this->info("Syncing modules for tenant {$tenantId} on plan '{$plan}'...");

        foreach ($allModules as $moduleKey) {
            $enabled = in_array($moduleKey, $planModules, true);

            TenantModule::updateOrCreate(
                ['tenant_id' => $tenantId, 'module_key' => $moduleKey],
                ['is_enabled' => $enabled]
            );

            $status = $enabled ? '<fg=green>enabled</>' : '<fg=red>disabled</>';
            $this->line("  {$moduleKey}: {$status}");
        }

        $this->info('Done.');

        return self::SUCCESS;
    }
}

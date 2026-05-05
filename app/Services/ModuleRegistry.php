<?php

namespace App\Services;

use App\Contracts\ModuleRegistryInterface;
use App\Exceptions\InvalidModuleKeyException;
use App\Models\TenantModule;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class ModuleRegistry implements ModuleRegistryInterface
{
    public function isActive(string $moduleKey, ?int $tenantId = null): bool
    {
        $this->validateModuleKey($moduleKey);

        // In local/testing environments, all modules are open
        if (in_array(config('app.env'), ['local', 'testing'])) {
            return true;
        }

        $tenantId ??= $this->currentTenantId();
        $cacheKey  = "module_active:{$tenantId}:{$moduleKey}";

        try {
            return Cache::remember($cacheKey, 60, fn () => $this->resolveActive($moduleKey, $tenantId));
        } catch (\Exception) {
            return $this->resolveActive($moduleKey, $tenantId);
        }
    }

    public function enable(string $moduleKey, int $tenantId): void
    {
        $this->validateModuleKey($moduleKey);

        TenantModule::updateOrCreate(
            ['tenant_id' => $tenantId, 'module_key' => $moduleKey],
            ['is_enabled' => true]
        );

        Cache::forget("module_active:{$tenantId}:{$moduleKey}");
    }

    public function disable(string $moduleKey, int $tenantId): void
    {
        $this->validateModuleKey($moduleKey);

        TenantModule::updateOrCreate(
            ['tenant_id' => $tenantId, 'module_key' => $moduleKey],
            ['is_enabled' => false]
        );

        Cache::forget("module_active:{$tenantId}:{$moduleKey}");
    }

    public function allForTenant(int $tenantId): Collection
    {
        $allModules = array_keys(config('pricing.modules', []));
        $dbRows     = TenantModule::where('tenant_id', $tenantId)
            ->whereIn('module_key', $allModules)
            ->get()
            ->keyBy('module_key');

        // Prefer the tenant's own plan; fall back to APP_PLAN for legacy/demo
        $plan        = \App\Models\Tenant::find($tenantId)?->plan ?? config('app.plan', 'free');
        $planModules = config("pricing.plans.{$plan}.modules", []);

        return collect($allModules)->map(function (string $key) use ($dbRows, $planModules) {
            if ($dbRows->has($key)) {
                return ['module_key' => $key, 'is_enabled' => $dbRows[$key]->is_enabled];
            }

            return ['module_key' => $key, 'is_enabled' => in_array($key, $planModules, true)];
        });
    }

    private function resolveActive(string $moduleKey, int $tenantId): bool
    {
        $row = TenantModule::where('tenant_id', $tenantId)
            ->where('module_key', $moduleKey)
            ->first();

        if ($row !== null) {
            return $row->is_enabled;
        }

        // Fall back to pricing plan default
        $plan        = config('app.plan', 'free');
        $planModules = config("pricing.plans.{$plan}.modules", []);

        return in_array($moduleKey, $planModules, true);
    }

    private function validateModuleKey(string $moduleKey): void
    {
        $validKeys = array_keys(config('pricing.modules', []));

        if (! in_array($moduleKey, $validKeys, true)) {
            throw new InvalidModuleKeyException($moduleKey);
        }
    }

    /**
     * Resolve the current tenant ID from the container binding set by ResolveTenant middleware,
     * falling back to the authenticated user's tenant, then to 1 for legacy/demo data.
     */
    private function currentTenantId(): int
    {
        try {
            $tenant = app(\App\Models\Tenant::class);
            if ($tenant instanceof \App\Models\Tenant) {
                return $tenant->id;
            }
        } catch (\Throwable) {
            // not bound
        }

        // Fall back to the authenticated user's tenant
        $user = auth()->user();
        if ($user && $user->tenant_id) {
            return $user->tenant_id;
        }

        return 1; // legacy / demo fallback
    }
}

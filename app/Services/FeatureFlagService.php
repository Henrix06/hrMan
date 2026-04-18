<?php

namespace App\Services;

use App\Contracts\FeatureFlagServiceInterface;
use App\Contracts\RuleServiceInterface;
use App\Events\FeatureFlagChanged;
use App\Models\FeatureFlag;
use Illuminate\Support\Facades\Cache;

class FeatureFlagService implements FeatureFlagServiceInterface
{
    public function __construct(private readonly RuleServiceInterface $rules) {}

    public function isEnabled(string $flag, ?int $tenantId = null): bool
    {
        $tenantId ??= 1;
        $cacheKey  = "feature_flag:{$tenantId}:{$flag}";

        try {
            return Cache::remember($cacheKey, 30, fn () => $this->resolve($flag, $tenantId));
        } catch (\Exception) {
            return $this->resolve($flag, $tenantId);
        }
    }

    public function enable(string $flag, int $tenantId): void
    {
        FeatureFlag::updateOrCreate(
            ['tenant_id' => $tenantId, 'flag_key' => $flag],
            ['is_enabled' => true]
        );

        Cache::forget("feature_flag:{$tenantId}:{$flag}");
        FeatureFlagChanged::dispatch($flag, $tenantId, true);
    }

    public function disable(string $flag, int $tenantId): void
    {
        FeatureFlag::updateOrCreate(
            ['tenant_id' => $tenantId, 'flag_key' => $flag],
            ['is_enabled' => false]
        );

        Cache::forget("feature_flag:{$tenantId}:{$flag}");
        FeatureFlagChanged::dispatch($flag, $tenantId, false);
    }

    public function sync(array $flags, int $tenantId): void
    {
        foreach ($flags as $flagKey => $enabled) {
            $enabled ? $this->enable($flagKey, $tenantId) : $this->disable($flagKey, $tenantId);
        }
    }

    private function resolve(string $flag, int $tenantId): bool
    {
        $row = FeatureFlag::where('tenant_id', $tenantId)->where('flag_key', $flag)->first();

        if ($row === null) {
            return true; // opt-out: unknown flags are enabled
        }

        if (! $row->is_enabled) {
            return false;
        }

        // Delegate to rules engine
        $context = (new EvaluationContext($tenantId))->toArray();
        $context['tenant_id'] = $tenantId;

        return $this->rules->evaluate($flag, $context);
    }
}

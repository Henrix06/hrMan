<?php

namespace App\Services;

use App\Contracts\RuleServiceInterface;
use App\Exceptions\InvalidRuleDefinitionException;
use App\Models\ModuleRule;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;

class RuleService implements RuleServiceInterface
{
    public function __construct(private readonly RuleEvaluator $evaluator) {}

    public function evaluate(string $subject, array $context = []): bool
    {
        $tenantId = $context['tenant_id'] ?? 1;
        $cacheKey = "module_rules:{$tenantId}:{$subject}";

        try {
            $rules = Cache::remember($cacheKey, 120, fn () => $this->loadRules($subject, $tenantId));
        } catch (\Exception) {
            $rules = $this->loadRules($subject, $tenantId);
        }

        if ($rules->isEmpty()) {
            return true;
        }

        foreach ($rules as $rule) {
            try {
                $passed = $this->evaluator->evaluateDefinition($rule->definition, $context);
            } catch (InvalidRuleDefinitionException $e) {
                Log::error('Invalid rule definition', [
                    'subject'  => $subject,
                    'rule_key' => $rule->rule_key,
                    'error'    => $e->getMessage(),
                ]);
                $passed = true; // permissive fallback
            }

            if (! $passed) {
                return false;
            }
        }

        return true;
    }

    public function upsert(string $subject, string $ruleKey, array $ruleDefinition): void
    {
        Gate::authorize('manage-modules');

        $tenantId = 1;

        ModuleRule::updateOrCreate(
            ['rule_key' => $ruleKey],
            [
                'tenant_id'  => $tenantId,
                'subject'    => $subject,
                'definition' => $ruleDefinition,
                'is_active'  => true,
            ]
        );

        $this->invalidateCache($subject, $tenantId);
    }

    public function remove(string $subject, string $ruleKey): void
    {
        Gate::authorize('manage-modules');

        $tenantId = 1;

        ModuleRule::where('rule_key', $ruleKey)->where('subject', $subject)->delete();

        $this->invalidateCache($subject, $tenantId);
    }

    private function loadRules(string $subject, int $tenantId)
    {
        return ModuleRule::where('tenant_id', $tenantId)
            ->where('subject', $subject)
            ->where('is_active', true)
            ->orderByDesc('priority')
            ->get();
    }

    private function invalidateCache(string $subject, int $tenantId): void
    {
        Cache::forget("module_rules:{$tenantId}:{$subject}");
    }
}

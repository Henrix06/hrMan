<?php

namespace App\Services;

use App\Exceptions\Plan\PlanChangeException;
use App\Models\AuditLog;
use App\Models\Tenant;
use App\Models\TenantModule;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class PlanService
{
    /**
     * Switch a company onto another plan from inside the app.
     *
     * Modules are enabled/disabled to match the new plan. When the company has
     * no active entitlement, a fresh trial window is opened.
     *
     * @return array<string, mixed>
     *
     * @throws PlanChangeException
     */
    public function switchPlan(Tenant $tenant, string $plan, ?User $user = null, ?string $billingCycle = null): array
    {
        $plans = config('pricing.plans', []);

        if (! array_key_exists($plan, $plans)) {
            throw PlanChangeException::unknownPlan($plan);
        }

        if (($plans[$plan]['monthly_price'] ?? null) === null) {
            throw PlanChangeException::contactSales($plans[$plan]['name'] ?? ucfirst($plan));
        }

        $previousPlan = $tenant->plan;

        if ($previousPlan === $plan) {
            throw PlanChangeException::samePlan();
        }

        $startedTrial = false;

        DB::transaction(function () use ($tenant, $plan, $billingCycle, &$startedTrial) {
            $isFree = config("pricing.plans.{$plan}.monthly_price") === 0;

            $tenant->plan = $plan;
            $tenant->status = 'active';
            $tenant->billing_cycle = $billingCycle ?? $tenant->billing_cycle ?? 'monthly';

            if ($isFree) {
                // The Free plan never expires.
                $tenant->trial_ends_at = null;
                $tenant->subscription_ends_at = null;
            } elseif ($tenant->subscription_ends_at?->isFuture() || $tenant->trial_ends_at?->isFuture()) {
                // Already entitled (licence or live trial) — leave the window alone.
            } else {
                $tenant->trial_ends_at = now()->addDays((int) config('pricing.trial_days', 14));
                $tenant->subscription_ends_at = null;
                $startedTrial = true;
            }

            $tenant->save();
        });

        $this->syncModules($tenant, $plan);

        $diff = $this->moduleDiff($previousPlan, $plan);

        if ($user !== null) {
            AuditLog::record(
                'update',
                "Changed plan from {$this->planName($previousPlan)} to {$this->planName($plan)}",
                $tenant,
                ['plan' => $previousPlan],
                ['plan' => $plan]
            );
        }

        return [
            'plan' => $plan,
            'plan_name' => $this->planName($plan),
            'previous_plan' => $previousPlan,
            'previous_plan_name' => $this->planName($previousPlan),
            'started_trial' => $startedTrial,
            'trial_ends_at' => $tenant->trial_ends_at,
            'subscription_ends_at' => $tenant->subscription_ends_at,
            'added_modules' => $diff['added'],
            'removed_modules' => $diff['removed'],
        ];
    }

    /**
     * Entitle a company to a plan for a fixed duration (used by licence redemption).
     */
    public function grantPlan(Tenant $tenant, string $plan, ?int $durationDays): ?Carbon
    {
        $tenant->plan = $plan;

        if ($durationDays === null) {
            $tenant->subscription_ends_at = null; // perpetual licence
        } else {
            $base = $tenant->subscription_ends_at?->isFuture()
                ? $tenant->subscription_ends_at->copy()
                : now();

            $tenant->subscription_ends_at = $base->addDays($durationDays);
        }

        $tenant->trial_ends_at = null; // a licence supersedes the free trial
        $tenant->status = 'active';
        $tenant->save();

        $this->syncModules($tenant, $plan);

        return $tenant->subscription_ends_at;
    }

    /**
     * Persist the plan's module set as the tenant's enabled modules.
     */
    public function syncModules(Tenant $tenant, string $plan): void
    {
        $planModules = $this->planModules($plan);

        foreach (array_keys(config('pricing.modules', [])) as $moduleKey) {
            TenantModule::updateOrCreate(
                ['tenant_id' => $tenant->id, 'module_key' => $moduleKey],
                ['is_enabled' => in_array($moduleKey, $planModules, true)]
            );

            Cache::forget("module_active:{$tenant->id}:{$moduleKey}");
        }
    }

    /**
     * @return list<string>
     */
    public function planModules(string $plan): array
    {
        return config("pricing.plans.{$plan}.modules", []);
    }

    public function planName(string $plan): string
    {
        return config("pricing.plans.{$plan}.name", ucfirst($plan));
    }

    /**
     * Module keys gained and lost when moving between two plans.
     *
     * @return array{added: list<string>, removed: list<string>}
     */
    public function moduleDiff(string $fromPlan, string $toPlan): array
    {
        return [
            'added' => array_values(array_diff($this->planModules($toPlan), $this->planModules($fromPlan))),
            'removed' => array_values(array_diff($this->planModules($fromPlan), $this->planModules($toPlan))),
        ];
    }
}

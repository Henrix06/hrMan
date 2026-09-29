<?php

namespace App\Livewire\Plan;

use App\Exceptions\Plan\PlanChangeException;
use App\Models\Tenant;
use App\Models\User;
use App\Services\PlanService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class ManagePlan extends Component
{
    public string $billing = 'monthly'; // 'monthly' | 'yearly'

    public ?string $confirmingPlan = null;

    public ?string $status_message = null;

    public ?string $error_message = null;

    public function mount(): void
    {
        $this->billing = Auth::user()?->tenant?->billing_cycle === 'yearly' ? 'yearly' : 'monthly';
    }

    public function toggleBilling(string $cycle): void
    {
        $this->billing = $cycle === 'yearly' ? 'yearly' : 'monthly';
    }

    public function confirm(string $plan): void
    {
        $this->confirmingPlan = $plan;
        $this->status_message = null;
        $this->error_message = null;
    }

    public function cancel(): void
    {
        $this->confirmingPlan = null;
    }

    public function switchPlan(PlanService $plans): void
    {
        $plan = $this->confirmingPlan;

        if ($plan === null) {
            return;
        }

        $this->status_message = null;
        $this->error_message = null;

        $user = Auth::user();
        $tenant = $user?->tenant;

        if (! $tenant instanceof Tenant) {
            $this->error_message = PlanChangeException::noWorkspace()->getMessage();
            $this->confirmingPlan = null;

            return;
        }

        try {
            $result = $plans->switchPlan($tenant, $plan, $user, $this->billing);
        } catch (PlanChangeException $e) {
            $this->error_message = $e->getMessage();
            $this->confirmingPlan = null;

            return;
        }

        $this->confirmingPlan = null;
        $this->status_message = $this->describeChange($result);

        $this->dispatch('plan-changed');
    }

    /**
     * @param  array<string, mixed>  $result
     */
    private function describeChange(array $result): string
    {
        $message = "You're now on the {$result['plan_name']} plan.";

        if ($result['started_trial'] && $result['trial_ends_at'] instanceof Carbon) {
            $message .= ' Your trial runs until '.$result['trial_ends_at']->format('j M Y').'.';
        } elseif ($result['subscription_ends_at'] instanceof Carbon) {
            $message .= ' Active until '.$result['subscription_ends_at']->format('j M Y').'.';
        }

        $labels = config('pricing.modules', []);

        $added = collect($result['added_modules'])->map(fn ($key) => $labels[$key] ?? $key);
        $lost = collect($result['removed_modules'])->map(fn ($key) => $labels[$key] ?? $key);

        if ($added->isNotEmpty()) {
            $message .= ' Enabled: '.$added->join(', ').'.';
        }

        if ($lost->isNotEmpty()) {
            $message .= ' Turned off: '.$lost->join(', ').'.';
        }

        return $message;
    }

    public function price(array $plan): string
    {
        if ($plan['monthly_price'] === null && $plan['yearly_price'] === null) {
            return 'Contact us';
        }

        if ($plan['monthly_price'] === 0) {
            return 'Free';
        }

        $amount = $this->billing === 'yearly' ? (int) $plan['yearly_price'] : (int) $plan['monthly_price'];

        return $this->billing === 'yearly'
            ? '$'.number_format($amount / 12, 0).'/mo'
            : '$'.number_format($amount, 0).'/mo';
    }

    /**
     * Modules gained and lost by moving from one plan to another.
     *
     * @return array{added: array<int, string>, removed: array<int, string>}
     */
    public function diffFor(string $plan, ?string $from): array
    {
        return app(PlanService::class)->moduleDiff((string) $from, $plan);
    }

    private function accessEndsAt(?Tenant $tenant): ?Carbon
    {
        if (! $tenant) {
            return null;
        }

        if ($tenant->subscription_ends_at?->isFuture()) {
            return $tenant->subscription_ends_at;
        }

        if ($tenant->trial_ends_at?->isFuture()) {
            return $tenant->trial_ends_at;
        }

        return null;
    }

    public function render()
    {
        $tenant = Auth::user()?->tenant;

        return view('livewire.plan.manage-plan', [
            'tenant' => $tenant,
            'plans' => config('pricing.plans', []),
            'modules' => config('pricing.modules', []),
            'planConfig' => $tenant ? $tenant->getPlanConfig() : [],
            'accessEndsAt' => $this->accessEndsAt($tenant),
            'isOnTrial' => $tenant?->isOnTrial() ?? false,
            'employeeCount' => $tenant ? User::where('tenant_id', $tenant->id)->count() : 0,
        ]);
    }
}

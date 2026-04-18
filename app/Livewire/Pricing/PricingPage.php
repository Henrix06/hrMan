<?php

namespace App\Livewire\Pricing;

use Livewire\Component;

class PricingPage extends Component
{
    public string $billing = 'monthly'; // 'monthly' | 'yearly'

    public function toggleBilling(string $cycle): void
    {
        $this->billing = $cycle;
    }

    public function getPlansProperty(): array
    {
        return config('pricing.plans');
    }

    public function getModulesProperty(): array
    {
        return config('pricing.modules');
    }

    public function price(array $plan): string
    {
        $amount = $this->billing === 'yearly' ? $plan['yearly_price'] : $plan['monthly_price'];

        if ($amount === 0) {
            return 'Free';
        }

        return '$' . number_format($this->billing === 'yearly' ? $amount / 12 : $amount, 0) . '/mo';
    }

    public function yearlySavings(array $plan): ?string
    {
        if ($plan['monthly_price'] === 0) return null;

        $annualIfMonthly = $plan['monthly_price'] * 12;
        $savings = $annualIfMonthly - $plan['yearly_price'];

        return $savings > 0 ? 'Save $' . $savings . '/yr' : null;
    }

    public function render()
    {
        return view('livewire.pricing.pricing-page')
            ->layout('layouts.pricing');
    }
}

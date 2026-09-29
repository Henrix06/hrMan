<?php

namespace App\Livewire\License;

use App\Exceptions\License\LicenseRedemptionException;
use App\Models\LicenseRedemption;
use App\Models\Tenant;
use App\Services\LicenseService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class RedeemLicense extends Component
{
    public string $code = '';

    public ?string $status_message = null;

    public ?string $error_message = null;

    public function redeem(LicenseService $licenses): void
    {
        $this->validate([
            'code' => ['required', 'string', 'max:64'],
        ]);

        $this->status_message = null;
        $this->error_message = null;

        $user = Auth::user();
        $tenant = $user?->tenant;

        if (! $tenant instanceof Tenant) {
            $this->error_message = LicenseRedemptionException::noWorkspace()->getMessage();

            return;
        }

        try {
            $redemption = $licenses->redeem($this->code, $tenant, $user);
        } catch (LicenseRedemptionException $e) {
            $this->error_message = $e->getMessage();

            return;
        }

        $this->code = '';

        $planName = $redemption->planName();

        $this->status_message = $redemption->subscription_ends_at !== null
            ? "Licence applied — {$planName} is active until {$redemption->subscription_ends_at->format('j M Y')}."
            : "Licence applied — {$planName} is now active.";

        $this->dispatch('license-redeemed');
    }

    public function render()
    {
        $tenant = Auth::user()?->tenant;

        return view('livewire.license.redeem-license', [
            'tenant' => $tenant,
            'planConfig' => $tenant?->getPlanConfig() ?? [],
            'isOnTrial' => $tenant?->isOnTrial() ?? false,
            'redemptions' => $tenant
                ? LicenseRedemption::with('licenseCode')
                    ->where('tenant_id', $tenant->id)
                    ->orderByDesc('created_at')
                    ->get()
                : collect(),
        ]);
    }
}

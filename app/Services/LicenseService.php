<?php

namespace App\Services;

use App\Exceptions\License\LicenseRedemptionException;
use App\Models\AuditLog;
use App\Models\LicenseCode;
use App\Models\LicenseRedemption;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class LicenseService
{
    public function __construct(private readonly PlanService $plans) {}

    /**
     * Redeem a licence code for a company.
     *
     * @throws LicenseRedemptionException
     */
    public function redeem(string $code, Tenant $tenant, ?User $user = null): LicenseRedemption
    {
        $license = $this->findByCode($code);

        if ($license === null) {
            throw LicenseRedemptionException::notFound();
        }

        if (! $license->is_active) {
            throw LicenseRedemptionException::inactive();
        }

        if ($license->isExpired()) {
            throw LicenseRedemptionException::expired();
        }

        if (! $license->hasRemainingRedemptions()) {
            throw LicenseRedemptionException::exhausted();
        }

        if ($license->redeemedByTenant($tenant->id)) {
            throw LicenseRedemptionException::alreadyRedeemed();
        }

        if (! array_key_exists($license->plan, config('pricing.plans', []))) {
            throw LicenseRedemptionException::unknownPlan($license->plan);
        }

        $redemption = DB::transaction(function () use ($license, $tenant, $user) {
            $subscriptionEndsAt = $this->plans->grantPlan($tenant, $license->plan, $license->duration_days);

            $license->increment('times_redeemed');

            return $license->redemptions()->create([
                'tenant_id' => $tenant->id,
                'user_id' => $user?->id,
                'plan' => $license->plan,
                'duration_days' => $license->duration_days,
                'subscription_ends_at' => $subscriptionEndsAt,
            ]);
        });

        if ($user !== null) {
            AuditLog::record(
                'update',
                "Redeemed a licence code for the {$license->planName()} plan",
                $tenant,
                [],
                ['plan' => $license->plan, 'licence_code_id' => $license->id]
            );
        }

        return $redemption;
    }

    /**
     * Find a code, tolerating lowercase input, stray whitespace and missing hyphens.
     */
    public function findByCode(string $code): ?LicenseCode
    {
        $normalized = LicenseCode::normalize($code);

        if ($normalized === '') {
            return null;
        }

        return LicenseCode::query()
            ->where('code', $normalized)
            ->orWhereRaw("REPLACE(code, '-', '') = ?", [str_replace('-', '', $normalized)])
            ->first();
    }
}

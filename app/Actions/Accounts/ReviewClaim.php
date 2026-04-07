<?php

namespace App\Actions\Accounts;

use App\Models\AuditLog;
use App\Models\HrNotification;
use App\Models\OvertimeClaim;
use App\Models\TransportClaim;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Generic claim reviewer — handles both OvertimeClaim and TransportClaim.
 */
class ReviewClaim
{
    public function __construct(private readonly DebitAccount $debitAccount) {}

    public function approve(Model $claim, User $reviewer, float $approvedAmount, ?string $notes = null): void
    {
        if ($claim->status !== 'pending') {
            throw new RuntimeException('Only pending claims can be approved.');
        }

        if (!$claim->account_id) {
            throw new RuntimeException('No account linked to this claim.');
        }

        DB::transaction(function () use ($claim, $reviewer, $approvedAmount, $notes) {
            $old = $claim->toArray();

            $claim->update([
                'status'          => 'approved',
                'approved_amount' => $approvedAmount,
                'reviewed_by'     => $reviewer->id,
                'reviewed_at'     => now(),
                'review_notes'    => $notes,
            ]);

            $this->debitAccount->execute(
                $claim->account,
                $approvedAmount,
                $this->categoryFor($claim),
                $this->descriptionFor($claim),
                $claim,
            );

            AuditLog::record('update', "Approved {$claim->reference} for {$approvedAmount}", $claim, $old, $claim->fresh()->toArray());

            HrNotification::notify(
                $claim->user_id,
                'claim_approved',
                'Claim Approved',
                "{$reviewer->name} approved your claim {$claim->reference} for " . number_format($approvedAmount, 2) . ".",
                $this->routeFor($claim),
            );
        });
    }

    public function reject(Model $claim, User $reviewer, ?string $notes = null): void
    {
        if ($claim->status !== 'pending') {
            throw new RuntimeException('Only pending claims can be rejected.');
        }

        $old = $claim->toArray();
        $claim->update([
            'status'       => 'rejected',
            'reviewed_by'  => $reviewer->id,
            'reviewed_at'  => now(),
            'review_notes' => $notes,
        ]);

        AuditLog::record('update', "Rejected {$claim->reference}", $claim, $old, $claim->fresh()->toArray());

        HrNotification::notify(
            $claim->user_id,
            'claim_rejected',
            'Claim Rejected',
            "{$reviewer->name} rejected your claim {$claim->reference}." . ($notes ? " Reason: {$notes}" : ''),
            $this->routeFor($claim),
        );
    }

    private function categoryFor(Model $claim): string
    {
        return $claim instanceof OvertimeClaim ? 'overtime' : 'transport';
    }

    private function descriptionFor(Model $claim): string
    {
        if ($claim instanceof OvertimeClaim) {
            return "Overtime: {$claim->reference} — {$claim->employee->name} on {$claim->work_date->format('M d, Y')}";
        }
        return "Transport: {$claim->reference} — {$claim->employee->name} ({$claim->origin} → {$claim->destination})";
    }

    private function routeFor(Model $claim): string
    {
        return $claim instanceof OvertimeClaim
            ? route('accounts.overtime')
            : route('accounts.transport');
    }
}

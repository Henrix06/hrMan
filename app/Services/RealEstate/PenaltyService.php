<?php

namespace App\Services\RealEstate;

use App\Actions\Accounts\DebitAccount;
use App\Models\RealEstate\RentInvoice;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PenaltyService
{
    public function __construct(
        private DebitAccount $debitAccount,
    ) {}

    /**
     * Calculate the penalty amount for a given invoice on $asOf.
     * Formula: penalty = rent_amount * (penalty_rate / 100) * months_overdue
     * Assumes $invoice->lease is already loaded.
     */
    public function calculatePenalty(RentInvoice $invoice, Carbon $asOf): float
    {
        $daysOverdue   = $invoice->due_date->diffInDays($asOf);
        $monthsOverdue = max(1, (int) ceil($daysOverdue / 30));
        $penaltyAmount = (float) $invoice->rent_amount * ($invoice->lease->penalty_rate / 100) * $monthsOverdue;

        return max(0.0, $penaltyAmount);
    }

    /**
     * Apply penalties to all overdue invoices past their grace period.
     * Called by ApplyLatePenaltiesJob daily.
     *
     * Postconditions:
     *   - penalty_amount updated on invoice (additive, not replaced)
     *   - total_amount = rent_amount + penalty_amount
     *   - AccountTransaction recorded per penalty application
     *   - Invoice status set to 'overdue'
     */
    public function applyPendingPenalties(Carbon $asOf): void
    {
        $invoices = RentInvoice::pendingPenalty($asOf)
            ->with(['lease.account'])
            ->get();

        foreach ($invoices as $invoice) {
            $lease       = $invoice->lease;
            $graceCutoff = $invoice->due_date->copy()->addDays($lease->grace_period_days);

            if ($asOf->lte($graceCutoff)) {
                continue;
            }

            $penaltyAmount = $this->calculatePenalty($invoice, $asOf);

            if ($penaltyAmount <= 0) {
                continue;
            }

            try {
                DB::transaction(function () use ($invoice, $lease, $penaltyAmount) {
                    $invoice->penalty_amount = $invoice->penalty_amount + $penaltyAmount;
                    $invoice->total_amount   = $invoice->rent_amount + $invoice->penalty_amount;
                    $invoice->status         = 'overdue';
                    $invoice->save();

                    if ($lease->account !== null) {
                        $this->debitAccount->execute(
                            $lease->account,
                            $penaltyAmount,
                            'late_penalty',
                            'Late penalty for ' . $invoice->reference,
                            $invoice,
                        );
                    }
                });
            } catch (\Throwable $e) {
                Log::error('Failed to apply late penalty for invoice.', [
                    'invoice_id' => $invoice->id,
                    'reference'  => $invoice->reference,
                    'error'      => $e->getMessage(),
                ]);
            }
        }
    }
}

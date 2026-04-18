<?php

namespace App\Services\RealEstate;

use App\Actions\Accounts\CreditAccount;
use App\Actions\Accounts\DebitAccount;
use App\Models\RealEstate\Lease;
use App\Models\RealEstate\RentInvoice;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

class RentInvoicingService
{
    public function __construct(
        private DebitAccount $debitAccount,
        private CreditAccount $creditAccount,
    ) {}

    /**
     * Generate invoices for all active leases due on $billingDate.
     * Idempotent: skips leases that already have an invoice for the billing period.
     */
    public function generateDueInvoices(Carbon $billingDate): Collection
    {
        $leases = Lease::dueOn($billingDate)
            ->with(['property', 'reTenant', 'account'])
            ->get();

        $created = collect();

        foreach ($leases as $lease) {
            $billingPeriod = $billingDate->copy()->startOfMonth();

            $alreadyExists = RentInvoice::where('lease_id', $lease->id)
                ->where('billing_period', $billingPeriod)
                ->exists();

            if ($alreadyExists) {
                continue;
            }

            DB::transaction(function () use ($lease, $billingDate, $billingPeriod, $created) {
                $invoice = RentInvoice::create([
                    'reference'      => RentInvoice::generateReference('RNT'),
                    'lease_id'       => $lease->id,
                    'billing_period' => $billingPeriod,
                    'due_date'       => $billingDate,
                    'rent_amount'    => $lease->monthly_rent,
                    'penalty_amount' => 0,
                    'total_amount'   => $lease->monthly_rent,
                    'status'         => 'invoiced',
                    'created_by'     => null,
                ]);

                if ($lease->account !== null) {
                    $this->debitAccount->execute(
                        $lease->account,
                        (float) $lease->monthly_rent,
                        'rent',
                        'Rent invoice ' . $invoice->reference,
                        $invoice,
                    );
                } else {
                    Log::warning('Rent invoice created without ledger entry: lease has no linked account.', [
                        'lease_id'   => $lease->id,
                        'invoice_id' => $invoice->id,
                        'reference'  => $invoice->reference,
                    ]);
                }

                $created->push($invoice);
            });
        }

        return $created;
    }

    /**
     * Mark an invoice as paid and record the credit transaction.
     * Preconditions: invoice status must be 'invoiced' or 'overdue'.
     */
    public function markPaid(RentInvoice $invoice, User $actor): RentInvoice
    {
        if (! in_array($invoice->status, ['invoiced', 'overdue'])) {
            throw new InvalidArgumentException(
                'Invoice cannot be marked as paid in its current status: ' . $invoice->status
            );
        }

        return DB::transaction(function () use ($invoice) {
            $invoice->load('lease.account');

            $txn = $this->creditAccount->execute(
                $invoice->lease->account,
                (float) $invoice->total_amount,
                'Rent payment for ' . $invoice->reference,
                'rent',
            );

            $invoice->status         = 'paid';
            $invoice->paid_at        = Carbon::today();
            $invoice->transaction_id = $txn->id;
            $invoice->save();

            return $invoice;
        });
    }

    /**
     * Return all overdue invoices with their lease, property, and tenant relationships.
     */
    public function getOverdueInvoices(): Collection
    {
        return RentInvoice::overdue()
            ->with(['lease.property', 'lease.reTenant'])
            ->get();
    }
}

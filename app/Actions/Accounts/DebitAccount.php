<?php

namespace App\Actions\Accounts;

use App\Models\Account;
use App\Models\AccountTransaction;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class DebitAccount
{
    /**
     * Debit an account and write an immutable ledger entry.
     * Must be called inside an existing DB transaction.
     */
    public function execute(
        Account $account,
        float $amount,
        string $category,
        string $description,
        ?Model $transactable = null,
    ): AccountTransaction {
        if ($amount <= 0) {
            throw new RuntimeException('Debit amount must be greater than zero.');
        }

        // Pessimistic lock to prevent race conditions
        $account = Account::lockForUpdate()->findOrFail($account->id);

        if (!$account->hasAvailableFunds($amount)) {
            throw new RuntimeException("Insufficient funds in account [{$account->code}]. Available: {$account->balance}.");
        }

        $newBalance = (float) $account->balance - $amount;
        $account->update(['balance' => $newBalance]);

        $txn = AccountTransaction::create([
            'account_id'        => $account->id,
            'created_by'        => auth()->id(),
            'reference'         => AccountTransaction::generateReference(),
            'type'              => 'debit',
            'amount'            => $amount,
            'balance_after'     => $newBalance,
            'category'          => $category,
            'description'       => $description,
            'transactable_type' => $transactable ? get_class($transactable) : null,
            'transactable_id'   => $transactable?->id,
        ]);

        // Seal with integrity hash after creation (so created_at is populated)
        $txn->refresh();
        $txn->update(['integrity_hash' => $txn->computeIntegrityHash()]);

        return $txn;
    }
}

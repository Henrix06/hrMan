<?php

namespace App\Actions\Accounts;

use App\Models\Account;
use App\Models\AccountTransaction;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class CreditAccount
{
    /**
     * Credit (top-up) an account and write an immutable ledger entry.
     */
    public function execute(
        Account $account,
        float $amount,
        string $description,
        string $category = 'top_up',
    ): AccountTransaction {
        if ($amount <= 0) {
            throw new RuntimeException('Credit amount must be greater than zero.');
        }

        $account = Account::lockForUpdate()->findOrFail($account->id);
        $newBalance = (float) $account->balance + $amount;
        $account->update(['balance' => $newBalance]);

        $txn = AccountTransaction::create([
            'account_id'    => $account->id,
            'created_by'    => auth()->id(),
            'reference'     => AccountTransaction::generateReference(),
            'type'          => 'credit',
            'amount'        => $amount,
            'balance_after' => $newBalance,
            'category'      => $category,
            'description'   => $description,
        ]);

        $txn->refresh();
        $txn->update(['integrity_hash' => $txn->computeIntegrityHash()]);

        return $txn;
    }
}

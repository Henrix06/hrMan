<?php

namespace App\Livewire\Accounts;

use App\Actions\Accounts\CreditAccount;
use App\Models\Account;
use App\Models\AccountTransaction;
use App\Models\AuditLog;
use App\Models\OvertimeClaim;
use App\Models\ProcurementRequest;
use App\Models\TransportClaim;
use Livewire\Component;
use Livewire\WithPagination;
use RuntimeException;

class AccountsDashboard extends Component
{
    use WithPagination;

    // Account modal
    public bool $showAccountModal = false;
    public bool $editingAccount = false;
    public ?int $editingAccountId = null;
    public string $acc_name = '';
    public string $acc_type = 'operating';
    public string $acc_description = '';
    public string $acc_budget_limit = '';

    // Top-up modal
    public bool $showTopUpModal = false;
    public ?int $topUpAccountId = null;
    public string $topup_amount = '';
    public string $topup_description = '';

    // Ledger modal
    public bool $showLedgerModal = false;
    public ?Account $ledgerAccount = null;

    public function openCreateAccount(): void
    {
        $this->authorize('manage accounts');
        $this->reset(['editingAccountId', 'acc_name', 'acc_description', 'acc_budget_limit']);
        $this->acc_type = 'operating';
        $this->editingAccount = false;
        $this->showAccountModal = true;
    }

    public function openEditAccount(int $id): void
    {
        $this->authorize('manage accounts');
        $account = Account::findOrFail($id);
        $this->editingAccountId = $id;
        $this->editingAccount = true;
        $this->acc_name = $account->name;
        $this->acc_type = $account->type;
        $this->acc_description = $account->description ?? '';
        $this->acc_budget_limit = (string) ($account->budget_limit ?? '');
        $this->showAccountModal = true;
    }

    public function saveAccount(): void
    {
        $this->authorize('manage accounts');

        $data = $this->validate([
            'acc_name'         => 'required|string|max:200',
            'acc_type'         => 'required|in:operating,petty_cash,payroll,reserve',
            'acc_description'  => 'nullable|string|max:500',
            'acc_budget_limit' => 'nullable|numeric|min:0',
        ]);

        if ($this->editingAccount && $this->editingAccountId) {
            $account = Account::findOrFail($this->editingAccountId);
            $old = $account->toArray();
            $account->update([
                'name'         => $data['acc_name'],
                'type'         => $data['acc_type'],
                'description'  => $data['acc_description'] ?: null,
                'budget_limit' => $data['acc_budget_limit'] ?: null,
            ]);
            AuditLog::record('update', "Updated account [{$account->code}] {$account->name}", $account, $old, $account->fresh()->toArray());
            session()->flash('message', 'Account updated.');
        } else {
            $account = Account::create([
                'name'         => $data['acc_name'],
                'code'         => Account::generateCode($data['acc_type']),
                'type'         => $data['acc_type'],
                'description'  => $data['acc_description'] ?: null,
                'budget_limit' => $data['acc_budget_limit'] ?: null,
                'balance'      => 0,
                'created_by'   => auth()->id(),
            ]);
            AuditLog::record('create', "Created account [{$account->code}] {$account->name}", $account);
            session()->flash('message', 'Account created.');
        }

        $this->showAccountModal = false;
    }

    public function toggleActive(int $id): void
    {
        $this->authorize('manage accounts');
        $account = Account::findOrFail($id);
        $account->update(['active' => !$account->active]);
        AuditLog::record('update', ($account->active ? 'Activated' : 'Deactivated') . " account [{$account->code}]", $account);
    }

    public function openTopUp(int $id): void
    {
        $this->authorize('manage accounts');
        $this->topUpAccountId = $id;
        $this->topup_amount = '';
        $this->topup_description = '';
        $this->showTopUpModal = true;
    }

    public function saveTopUp(): void
    {
        $this->authorize('manage accounts');

        $this->validate([
            'topup_amount'      => 'required|numeric|min:0.01',
            'topup_description' => 'required|string|max:300',
        ]);

        $account = Account::findOrFail($this->topUpAccountId);

        try {
            \Illuminate\Support\Facades\DB::transaction(function () use ($account) {
                app(CreditAccount::class)->execute(
                    $account,
                    (float) $this->topup_amount,
                    $this->topup_description,
                    'top_up',
                );
            });
            AuditLog::record('create', "Topped up account [{$account->code}] by {$this->topup_amount}", $account);
            session()->flash('message', 'Account topped up successfully.');
        } catch (RuntimeException $e) {
            session()->flash('error', $e->getMessage());
        }

        $this->showTopUpModal = false;
    }

    public function viewLedger(int $id): void
    {
        $this->ledgerAccount = Account::findOrFail($id);
        $this->showLedgerModal = true;
    }

    public function render()
    {
        $this->authorize('view accounts');

        $accounts = Account::withCount([
            'transactions',
        ])->orderBy('type')->orderBy('name')->get();

        $pendingCounts = [
            'procurement' => ProcurementRequest::where('status', 'pending')->count(),
            'overtime'    => OvertimeClaim::where('status', 'pending')->count(),
            'transport'   => TransportClaim::where('status', 'pending')->count(),
        ];

        $ledgerEntries = $this->ledgerAccount
            ? AccountTransaction::with('creator:id,name')
                ->where('account_id', $this->ledgerAccount->id)
                ->orderByDesc('created_at')
                ->paginate(20)
            : null;

        return view('livewire.accounts.dashboard', compact('accounts', 'pendingCounts', 'ledgerEntries'));
    }
}

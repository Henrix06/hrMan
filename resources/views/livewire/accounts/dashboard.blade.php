<div>
    @if(session('message'))
        <div class="mb-6 px-4 py-3 rounded-xl bg-green-50 border border-green-200 text-green-700 text-sm">{{ session('message') }}</div>
    @endif
    @if(session('error'))
        <div class="mb-6 px-4 py-3 rounded-xl bg-red-50 border border-red-200 text-red-700 text-sm">{{ session('error') }}</div>
    @endif

    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
        <div>
            <h2 class="text-2xl md:text-3xl font-extrabold text-slate-900 font-headline">Accounts</h2>
            <p class="text-slate-500 text-sm mt-1">Company fund management and financial overview</p>
        </div>
        @can('manage accounts')
        <button wire:click="openCreateAccount"
            class="bg-gradient-to-br from-primary to-primary-container text-on-primary px-5 py-2.5 rounded-xl font-bold font-headline shadow-lg shadow-primary/20 hover:opacity-90 transition-opacity flex items-center justify-center gap-2 w-full sm:w-auto">
            <span class="material-symbols-outlined text-sm">add</span> New Account
        </button>
        @endcan
    </div>

    {{-- Pending approvals summary --}}
    @can('approve procurement')
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
        @php
            $pendingLinks = [
                'procurement' => ['route' => route('accounts.procurement'), 'icon' => 'shopping_cart', 'label' => 'Procurement', 'color' => 'text-violet-600 bg-violet-50'],
                'overtime'    => ['route' => route('accounts.overtime'),    'icon' => 'schedule',       'label' => 'Overtime',    'color' => 'text-amber-600 bg-amber-50'],
                'transport'   => ['route' => route('accounts.transport'),   'icon' => 'directions_car', 'label' => 'Transport',   'color' => 'text-blue-600 bg-blue-50'],
            ];
        @endphp
        @foreach($pendingCounts as $key => $count)
        <a href="{{ $pendingLinks[$key]['route'] }}" wire:navigate class="glass-card rounded-2xl p-5 flex items-center gap-4 hover:shadow-md transition-shadow">
            <div class="w-11 h-11 rounded-xl {{ $pendingLinks[$key]['color'] }} flex items-center justify-center flex-shrink-0">
                <span class="material-symbols-outlined text-[20px]">{{ $pendingLinks[$key]['icon'] }}</span>
            </div>
            <div>
                <p class="text-2xl font-extrabold text-slate-900 font-headline">{{ $count }}</p>
                <p class="text-xs text-slate-500">Pending {{ $pendingLinks[$key]['label'] }}</p>
            </div>
        </a>
        @endforeach
    </div>
    @endcan

    {{-- Account cards --}}
    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-5 mb-8">
        @forelse($accounts as $account)
        @php
            $typeColors = [
                'operating'  => 'from-primary to-primary-container',
                'petty_cash' => 'from-amber-400 to-orange-500',
                'payroll'    => 'from-emerald-500 to-teal-500',
                'reserve'    => 'from-slate-500 to-slate-700',
            ];
            $gradient = $typeColors[$account->type] ?? 'from-slate-400 to-slate-600';
            $pct = $account->budget_limit ? min(100, round(((float)$account->balance / (float)$account->budget_limit) * 100)) : null;
        @endphp
        <div class="glass-card rounded-[1.5rem] overflow-hidden {{ !$account->active ? 'opacity-60' : '' }}">
            <div class="bg-gradient-to-br {{ $gradient }} p-5 text-white">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-widest opacity-80">{{ str_replace('_', ' ', $account->type) }}</p>
                        <p class="text-xl font-extrabold font-headline mt-1">{{ $account->name }}</p>
                        <p class="text-xs opacity-70 mt-0.5">{{ $account->code }}</p>
                    </div>
                    <span class="material-symbols-outlined opacity-60 text-3xl">account_balance_wallet</span>
                </div>
                <p class="text-3xl font-extrabold font-headline mt-4">{{ number_format((float)$account->balance, 2) }}</p>
                @if($account->budget_limit)
                <p class="text-xs opacity-70 mt-1">Budget: {{ number_format((float)$account->budget_limit, 2) }}</p>
                @endif
            </div>
            <div class="p-4">
                @if($pct !== null)
                <div class="mb-3">
                    <div class="flex justify-between text-xs text-slate-500 mb-1">
                        <span>Balance vs Budget</span><span>{{ $pct }}%</span>
                    </div>
                    <div class="w-full bg-slate-100 rounded-full h-1.5">
                        <div class="h-1.5 rounded-full {{ $pct < 25 ? 'bg-red-500' : ($pct < 50 ? 'bg-amber-500' : 'bg-emerald-500') }}" style="width:{{ $pct }}%"></div>
                    </div>
                </div>
                @endif
                <div class="flex items-center gap-2">
                    @can('manage accounts')
                    <button wire:click="openTopUp({{ $account->id }})" class="flex-1 text-xs font-bold py-2 rounded-xl bg-primary/10 text-primary hover:bg-primary/20 transition-colors flex items-center justify-center gap-1">
                        <span class="material-symbols-outlined text-[14px]">add_circle</span> Top Up
                    </button>
                    <button wire:click="openEditAccount({{ $account->id }})" class="p-2 rounded-xl text-slate-400 hover:text-primary hover:bg-slate-50 transition-colors">
                        <span class="material-symbols-outlined text-[16px]">edit</span>
                    </button>
                    <button wire:click="toggleActive({{ $account->id }})" title="{{ $account->active ? 'Deactivate' : 'Activate' }}" class="p-2 rounded-xl text-slate-400 hover:text-amber-500 hover:bg-slate-50 transition-colors">
                        <span class="material-symbols-outlined text-[16px]">{{ $account->active ? 'toggle_on' : 'toggle_off' }}</span>
                    </button>
                    @endcan
                    <button wire:click="viewLedger({{ $account->id }})" class="p-2 rounded-xl text-slate-400 hover:text-primary hover:bg-slate-50 transition-colors" title="View Ledger">
                        <span class="material-symbols-outlined text-[16px]">receipt_long</span>
                    </button>
                </div>
            </div>
        </div>
        @empty
        <div class="col-span-3 glass-card rounded-[2rem] py-20 text-center text-slate-400">
            <span class="material-symbols-outlined text-5xl block mb-3">account_balance_wallet</span>
            <p>No accounts yet.</p>
        </div>
        @endforelse
    </div>

    {{-- Quick links --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        @php
            $modules = [
                ['route' => route('accounts.procurement'), 'icon' => 'shopping_cart',  'label' => 'Procurement',       'desc' => 'Purchase requests & approvals',  'color' => 'bg-violet-50 text-violet-600'],
                ['route' => route('accounts.overtime'),    'icon' => 'schedule',        'label' => 'Overtime Claims',    'desc' => 'Submit & review overtime pay',   'color' => 'bg-amber-50 text-amber-600'],
                ['route' => route('accounts.transport'),   'icon' => 'directions_car',  'label' => 'Transport Refunds', 'desc' => 'Travel & transport reimbursements','color' => 'bg-blue-50 text-blue-600'],
            ];
        @endphp
        @foreach($modules as $mod)
        <a href="{{ $mod['route'] }}" wire:navigate class="glass-card rounded-2xl p-5 flex items-center gap-4 hover:shadow-md transition-shadow group">
            <div class="w-12 h-12 rounded-2xl {{ $mod['color'] }} flex items-center justify-center flex-shrink-0 group-hover:scale-105 transition-transform">
                <span class="material-symbols-outlined">{{ $mod['icon'] }}</span>
            </div>
            <div>
                <p class="font-bold text-slate-800 font-headline">{{ $mod['label'] }}</p>
                <p class="text-xs text-slate-500">{{ $mod['desc'] }}</p>
            </div>
            <span class="material-symbols-outlined text-slate-300 ml-auto">chevron_right</span>
        </a>
        @endforeach
    </div>

    {{-- ── Create/Edit Account Modal ── --}}
    @if($showAccountModal)
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm p-4">
        <div class="bg-white rounded-[2rem] shadow-2xl w-full max-w-md p-6 md:p-8">
            <h3 class="text-2xl font-bold font-headline text-slate-900 mb-6">{{ $editingAccount ? 'Edit Account' : 'New Account' }}</h3>
            <form wire:submit="saveAccount" class="space-y-4">
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1">Account Name *</label>
                    <input wire:model="acc_name" type="text" class="w-full px-4 py-2 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-primary/20 focus:outline-none"/>
                    @error('acc_name') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1">Type *</label>
                    <select wire:model="acc_type" class="w-full px-4 py-2 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-primary/20 focus:outline-none">
                        <option value="operating">Operating</option>
                        <option value="petty_cash">Petty Cash</option>
                        <option value="payroll">Payroll</option>
                        <option value="reserve">Reserve</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1">Budget Limit</label>
                    <input wire:model="acc_budget_limit" type="number" step="0.01" min="0" placeholder="Optional" class="w-full px-4 py-2 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-primary/20 focus:outline-none"/>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1">Description</label>
                    <textarea wire:model="acc_description" rows="2" class="w-full px-4 py-2 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-primary/20 focus:outline-none"></textarea>
                </div>
                <div class="flex gap-3 pt-2">
                    <button type="submit" class="flex-1 bg-primary text-on-primary py-2.5 rounded-xl font-bold font-headline hover:opacity-90 transition-opacity">{{ $editingAccount ? 'Update' : 'Create' }}</button>
                    <button type="button" wire:click="$set('showAccountModal', false)" class="flex-1 bg-slate-100 text-slate-700 py-2.5 rounded-xl font-bold font-headline hover:bg-slate-200 transition-colors">Cancel</button>
                </div>
            </form>
        </div>
    </div>
    @endif

    {{-- ── Top-Up Modal ── --}}
    @if($showTopUpModal)
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm p-4">
        <div class="bg-white rounded-[2rem] shadow-2xl w-full max-w-sm p-6 md:p-8">
            <h3 class="text-2xl font-bold font-headline text-slate-900 mb-6">Top Up Account</h3>
            <form wire:submit="saveTopUp" class="space-y-4">
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1">Amount *</label>
                    <input wire:model="topup_amount" type="number" step="0.01" min="0.01" class="w-full px-4 py-2 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-primary/20 focus:outline-none"/>
                    @error('topup_amount') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1">Description *</label>
                    <input wire:model="topup_description" type="text" placeholder="e.g. Q2 budget allocation" class="w-full px-4 py-2 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-primary/20 focus:outline-none"/>
                    @error('topup_description') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div class="flex gap-3 pt-2">
                    <button type="submit" class="flex-1 bg-emerald-500 text-white py-2.5 rounded-xl font-bold font-headline hover:opacity-90 transition-opacity">Credit Account</button>
                    <button type="button" wire:click="$set('showTopUpModal', false)" class="flex-1 bg-slate-100 text-slate-700 py-2.5 rounded-xl font-bold font-headline hover:bg-slate-200 transition-colors">Cancel</button>
                </div>
            </form>
        </div>
    </div>
    @endif

    {{-- ── Ledger Modal ── --}}
    @if($showLedgerModal && $ledgerAccount)
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm p-4">
        <div class="bg-white rounded-[2rem] shadow-2xl w-full max-w-2xl p-6 md:p-8 max-h-[90vh] overflow-y-auto">
            <div class="flex items-start justify-between mb-6">
                <div>
                    <h3 class="text-2xl font-bold font-headline text-slate-900">{{ $ledgerAccount->name }}</h3>
                    <p class="text-sm text-slate-500">{{ $ledgerAccount->code }} · Transaction Ledger</p>
                </div>
                <button wire:click="$set('showLedgerModal', false)" class="text-slate-400 hover:text-slate-700">
                    <span class="material-symbols-outlined">close</span>
                </button>
            </div>
            @if($ledgerEntries && $ledgerEntries->count())
            <div class="space-y-2">
                @foreach($ledgerEntries as $txn)
                @php $integrityOk = $txn->integrity_hash ? $txn->verifyIntegrity() : null; @endphp
                <div class="flex items-center gap-4 p-3 rounded-xl border border-slate-100 hover:bg-slate-50 transition-colors">
                    <div class="w-8 h-8 rounded-full flex items-center justify-center flex-shrink-0 {{ $txn->type === 'credit' ? 'bg-emerald-100 text-emerald-600' : 'bg-red-100 text-red-600' }}">
                        <span class="material-symbols-outlined text-[14px]">{{ $txn->type === 'credit' ? 'arrow_downward' : 'arrow_upward' }}</span>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-semibold text-slate-800 truncate">{{ $txn->description }}</p>
                        <p class="text-xs text-slate-400">{{ $txn->reference }} · {{ $txn->created_at->format('M d, Y H:i') }} · {{ $txn->creator->name }}</p>
                    </div>
                    <div class="text-right flex-shrink-0">
                        <p class="font-bold {{ $txn->type === 'credit' ? 'text-emerald-600' : 'text-red-600' }}">
                            {{ $txn->type === 'credit' ? '+' : '-' }}{{ number_format((float)$txn->amount, 2) }}
                        </p>
                        <p class="text-xs text-slate-400">Bal: {{ number_format((float)$txn->balance_after, 2) }}</p>
                    </div>
                    @if($integrityOk === true)
                        <span title="Verified" class="text-emerald-500 flex-shrink-0"><span class="material-symbols-outlined text-[16px]">verified</span></span>
                    @elseif($integrityOk === false)
                        <span title="Integrity FAILED" class="text-red-500 flex-shrink-0"><span class="material-symbols-outlined text-[16px]">gpp_bad</span></span>
                    @endif
                </div>
                @endforeach
            </div>
            <div class="mt-4">{{ $ledgerEntries->links() }}</div>
            @else
            <p class="text-slate-400 text-sm text-center py-10">No transactions yet.</p>
            @endif
        </div>
    </div>
    @endif
</div>

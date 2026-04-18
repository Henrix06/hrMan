<div>
    @if (session()->has('message'))
        <div class="mb-6 px-4 py-3 rounded-xl bg-green-50 border border-green-200 text-green-700 text-sm">{{ session('message') }}</div>
    @endif
    @if (session()->has('error'))
        <div class="mb-6 px-4 py-3 rounded-xl bg-red-50 border border-red-200 text-red-700 text-sm">{{ session('error') }}</div>
    @endif

    <div class="flex items-center justify-between mb-8">
        <div>
            <h2 class="text-3xl font-extrabold text-slate-900 font-headline">Lease Management</h2>
            <p class="text-slate-500 text-sm mt-1">Create, activate, and manage lease agreements</p>
        </div>
        @can('real_estate.manage_leases')
        <button wire:click="openCreate" class="bg-gradient-to-br from-primary to-primary-container text-on-primary px-5 py-2.5 rounded-xl font-bold font-headline shadow-lg shadow-primary/20 hover:opacity-90 transition-opacity flex items-center gap-2">
            <span class="material-symbols-outlined text-sm">add</span> New Lease
        </button>
        @endcan
    </div>

    <div class="glass-card rounded-[1.5rem] p-4 mb-6 flex flex-wrap gap-3 items-center">
        <input wire:model.live.debounce.300ms="search" type="text" placeholder="Search by reference, property, tenant..."
            class="px-4 py-2 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-primary/20 focus:outline-none flex-1 min-w-48"/>
        <select wire:model.live="statusFilter" class="px-4 py-2 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-primary/20 focus:outline-none">
            <option value="">All Statuses</option>
            <option value="draft">Draft</option>
            <option value="active">Active</option>
            <option value="expired">Expired</option>
            <option value="terminated">Terminated</option>
            <option value="renewed">Renewed</option>
        </select>
    </div>

    <div class="glass-card rounded-[1.5rem] overflow-hidden">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-slate-100 text-left">
                    <th class="px-5 py-4 font-bold text-slate-600 text-xs uppercase tracking-widest">Reference</th>
                    <th class="px-5 py-4 font-bold text-slate-600 text-xs uppercase tracking-widest">Property</th>
                    <th class="px-5 py-4 font-bold text-slate-600 text-xs uppercase tracking-widest">Tenant</th>
                    <th class="px-5 py-4 font-bold text-slate-600 text-xs uppercase tracking-widest">Monthly Rent</th>
                    <th class="px-5 py-4 font-bold text-slate-600 text-xs uppercase tracking-widest">Start / End</th>
                    <th class="px-5 py-4 font-bold text-slate-600 text-xs uppercase tracking-widest">Status</th>
                    <th class="px-5 py-4 font-bold text-slate-600 text-xs uppercase tracking-widest">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-50">
                @forelse($leases as $lease)
                @php
                    $statusColors = [
                        'draft'      => 'bg-slate-100 text-slate-500',
                        'active'     => 'bg-green-100 text-green-700',
                        'expired'    => 'bg-orange-100 text-orange-700',
                        'terminated' => 'bg-red-100 text-red-700',
                        'renewed'    => 'bg-blue-100 text-blue-700',
                    ];
                @endphp
                <tr class="hover:bg-slate-50/50 transition-colors">
                    <td class="px-5 py-4 text-xs text-slate-400 font-mono">{{ $lease->reference }}</td>
                    <td class="px-5 py-4 font-semibold text-slate-800">{{ $lease->property?->name ?? '—' }}</td>
                    <td class="px-5 py-4 text-slate-600">{{ $lease->reTenant?->name ?? '—' }}</td>
                    <td class="px-5 py-4 text-slate-600">R {{ number_format($lease->monthly_rent, 2) }}</td>
                    <td class="px-5 py-4 text-slate-600 text-xs">
                        {{ $lease->start_date->format('M d, Y') }}<br>
                        <span class="text-slate-400">{{ $lease->end_date->format('M d, Y') }}</span>
                    </td>
                    <td class="px-5 py-4">
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-widest {{ $statusColors[$lease->status] ?? 'bg-slate-100 text-slate-500' }}">
                            {{ $lease->status }}
                        </span>
                    </td>
                    <td class="px-5 py-4">
                        <div class="flex items-center gap-2">
                            @can('real_estate.manage_leases')
                            @if($lease->status === 'draft')
                            <button wire:click="activateLease({{ $lease->id }})" title="Activate" class="text-slate-400 hover:text-green-600 transition-colors">
                                <span class="material-symbols-outlined text-sm">play_circle</span>
                            </button>
                            @endif
                            @if($lease->status === 'active')
                            <button wire:click="openTerminate({{ $lease->id }})" title="Terminate" class="text-slate-400 hover:text-red-500 transition-colors">
                                <span class="material-symbols-outlined text-sm">cancel</span>
                            </button>
                            @endif
                            @endcan
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="py-20 text-center text-slate-400">
                        <span class="material-symbols-outlined text-5xl block mb-3">description</span>
                        <p>No leases found.</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $leases->links() }}</div>

    {{-- Create Lease Modal --}}
    @if($showCreateModal)
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm">
        <div class="bg-white rounded-[2rem] shadow-2xl w-full max-w-2xl mx-4 p-8 max-h-[90vh] overflow-y-auto">
            <h3 class="text-2xl font-bold font-headline text-slate-900 mb-6">New Lease</h3>
            <form wire:submit="saveLease" class="space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1">Property *</label>
                        <select wire:model="property_id" class="w-full px-4 py-2 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-primary/20 focus:outline-none">
                            <option value="">— Select Property —</option>
                            @foreach($properties as $p)
                                <option value="{{ $p->id }}">{{ $p->name }} ({{ $p->reference }})</option>
                            @endforeach
                        </select>
                        @error('property_id') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1">Tenant *</label>
                        <select wire:model="re_tenant_id" class="w-full px-4 py-2 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-primary/20 focus:outline-none">
                            <option value="">— Select Tenant —</option>
                            @foreach($tenants as $t)
                                <option value="{{ $t->id }}">{{ $t->name }} ({{ $t->reference }})</option>
                            @endforeach
                        </select>
                        @error('re_tenant_id') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1">Start Date *</label>
                        <input wire:model="start_date" type="date" class="w-full px-4 py-2 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-primary/20 focus:outline-none"/>
                        @error('start_date') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1">End Date *</label>
                        <input wire:model="end_date" type="date" class="w-full px-4 py-2 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-primary/20 focus:outline-none"/>
                        @error('end_date') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1">Monthly Rent *</label>
                        <input wire:model="monthly_rent" type="number" step="0.01" min="0" class="w-full px-4 py-2 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-primary/20 focus:outline-none"/>
                        @error('monthly_rent') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1">Deposit Amount</label>
                        <input wire:model="deposit_amount" type="number" step="0.01" min="0" class="w-full px-4 py-2 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-primary/20 focus:outline-none"/>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1">Payment Due Day</label>
                        <input wire:model="payment_due_day" type="number" min="1" max="28" class="w-full px-4 py-2 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-primary/20 focus:outline-none"/>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1">Penalty Rate (%)</label>
                        <input wire:model="penalty_rate" type="number" step="0.01" min="0" max="100" class="w-full px-4 py-2 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-primary/20 focus:outline-none"/>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1">Grace Period (days)</label>
                        <input wire:model="grace_period_days" type="number" min="0" class="w-full px-4 py-2 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-primary/20 focus:outline-none"/>
                    </div>
                    <div class="flex items-center gap-2 pt-5">
                        <input wire:model="deposit_paid" type="checkbox" id="deposit_paid" class="rounded"/>
                        <label for="deposit_paid" class="text-sm font-semibold text-slate-700">Deposit Paid</label>
                    </div>
                </div>
                <div class="flex gap-3 pt-2">
                    <button type="submit" class="flex-1 bg-primary text-on-primary py-2.5 rounded-xl font-bold font-headline hover:opacity-90 transition-opacity">Create Lease</button>
                    <button type="button" wire:click="$set('showCreateModal', false)" class="flex-1 bg-slate-100 text-slate-700 py-2.5 rounded-xl font-bold font-headline hover:bg-slate-200 transition-colors">Cancel</button>
                </div>
            </form>
        </div>
    </div>
    @endif

    {{-- Terminate Modal --}}
    @if($showTerminateModal)
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm">
        <div class="bg-white rounded-[2rem] shadow-2xl w-full max-w-md mx-4 p-8">
            <h3 class="text-2xl font-bold font-headline text-slate-900 mb-2">Terminate Lease</h3>
            <p class="text-sm text-slate-500 mb-6">Please provide a reason for terminating this lease.</p>
            <form wire:submit="confirmTerminate" class="space-y-4">
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1">Termination Reason *</label>
                    <textarea wire:model="terminationReason" rows="4" class="w-full px-4 py-2 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-primary/20 focus:outline-none"></textarea>
                    @error('terminationReason') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div class="flex gap-3 pt-2">
                    <button type="submit" class="flex-1 bg-red-500 text-white py-2.5 rounded-xl font-bold font-headline hover:opacity-90 transition-opacity">Terminate</button>
                    <button type="button" wire:click="$set('showTerminateModal', false)" class="flex-1 bg-slate-100 text-slate-700 py-2.5 rounded-xl font-bold font-headline hover:bg-slate-200 transition-colors">Cancel</button>
                </div>
            </form>
        </div>
    </div>
    @endif
</div>

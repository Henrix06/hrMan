<div>
    @if(session('message'))
        <div class="mb-6 px-4 py-3 rounded-xl bg-green-50 border border-green-200 text-green-700 text-sm">{{ session('message') }}</div>
    @endif
    @if(session('error'))
        <div class="mb-6 px-4 py-3 rounded-xl bg-red-50 border border-red-200 text-red-700 text-sm">{{ session('error') }}</div>
    @endif

    <div class="flex items-center justify-between mb-8">
        <div>
            <h2 class="text-3xl font-extrabold text-slate-900 font-headline">Overtime Claims</h2>
            <p class="text-slate-500 text-sm mt-1">Submit and manage overtime pay claims</p>
        </div>
        <button wire:click="openCreate" class="bg-gradient-to-br from-primary to-primary-container text-on-primary px-5 py-2.5 rounded-xl font-bold font-headline shadow-lg shadow-primary/20 hover:opacity-90 transition-opacity flex items-center gap-2">
            <span class="material-symbols-outlined text-sm">add</span> New Claim
        </button>
    </div>

    <div class="glass-card rounded-[1.5rem] p-4 mb-6 flex flex-wrap gap-3">
        <input wire:model.live.debounce.300ms="search" type="text" placeholder="Search reference or employee..."
            class="px-4 py-2 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-primary/20 focus:outline-none flex-1 min-w-48"/>
        <select wire:model.live="statusFilter" class="px-4 py-2 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-primary/20 focus:outline-none">
            <option value="">All Statuses</option>
            <option value="draft">Draft</option>
            <option value="pending">Pending</option>
            <option value="approved">Approved</option>
            <option value="rejected">Rejected</option>
            <option value="paid">Paid</option>
        </select>
    </div>

    <div class="glass-card rounded-[1.5rem] overflow-hidden">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-slate-100 text-left">
                    <th class="px-5 py-4 font-bold text-slate-600 text-xs uppercase tracking-widest">Employee</th>
                    <th class="px-5 py-4 font-bold text-slate-600 text-xs uppercase tracking-widest">Work Date</th>
                    <th class="px-5 py-4 font-bold text-slate-600 text-xs uppercase tracking-widest">Hours</th>
                    <th class="px-5 py-4 font-bold text-slate-600 text-xs uppercase tracking-widest">Claim</th>
                    <th class="px-5 py-4 font-bold text-slate-600 text-xs uppercase tracking-widest">Status</th>
                    <th class="px-5 py-4 font-bold text-slate-600 text-xs uppercase tracking-widest">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-50">
                @forelse($claims as $claim)
                @php
                    $statusColors = ['draft'=>'bg-slate-100 text-slate-500','pending'=>'bg-amber-100 text-amber-700','approved'=>'bg-green-100 text-green-700','rejected'=>'bg-red-100 text-red-700','paid'=>'bg-blue-100 text-blue-700'];
                @endphp
                <tr class="hover:bg-slate-50/50 transition-colors">
                    <td class="px-5 py-4">
                        <p class="font-bold text-slate-800">{{ $claim->employee->name }}</p>
                        <p class="text-xs text-slate-400">{{ $claim->reference }}</p>
                    </td>
                    <td class="px-5 py-4 text-slate-600">
                        {{ $claim->work_date->format('M d, Y') }}
                        <p class="text-xs text-slate-400">{{ $claim->start_time }} – {{ $claim->end_time }}</p>
                    </td>
                    <td class="px-5 py-4">
                        <p class="font-bold text-slate-800">{{ $claim->hours }}h</p>
                        <p class="text-xs text-slate-400">×{{ $claim->multiplier }}</p>
                    </td>
                    <td class="px-5 py-4">
                        <p class="font-bold text-slate-800">{{ number_format((float)$claim->claim_amount, 2) }}</p>
                        @if($claim->approved_amount)
                            <p class="text-xs text-green-600 font-semibold">Approved: {{ number_format((float)$claim->approved_amount, 2) }}</p>
                        @endif
                    </td>
                    <td class="px-5 py-4">
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-widest {{ $statusColors[$claim->status] }}">{{ $claim->status }}</span>
                    </td>
                    <td class="px-5 py-4">
                        <div class="flex items-center gap-2">
                            @if($claim->status === 'draft')
                                <button wire:click="openEdit({{ $claim->id }})" class="text-slate-400 hover:text-primary transition-colors" title="Edit">
                                    <span class="material-symbols-outlined text-sm">edit</span>
                                </button>
                                <button wire:click="cancel({{ $claim->id }})" wire:confirm="Remove this draft?" class="text-slate-400 hover:text-error transition-colors" title="Delete">
                                    <span class="material-symbols-outlined text-sm">delete</span>
                                </button>
                            @endif
                            @if($canReview && $claim->status === 'pending')
                                <button wire:click="openReview({{ $claim->id }}, 'approve')" class="text-slate-400 hover:text-green-600 transition-colors" title="Approve">
                                    <span class="material-symbols-outlined text-sm">check_circle</span>
                                </button>
                                <button wire:click="openReview({{ $claim->id }}, 'reject')" class="text-slate-400 hover:text-red-500 transition-colors" title="Reject">
                                    <span class="material-symbols-outlined text-sm">cancel</span>
                                </button>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="py-20 text-center text-slate-400">
                        <span class="material-symbols-outlined text-5xl block mb-3">schedule</span>
                        <p>No overtime claims found.</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $claims->links() }}</div>

    {{-- ── Create/Edit Modal ── --}}
    @if($showModal)
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm">
        <div class="bg-white rounded-[2rem] shadow-2xl w-full max-w-lg mx-4 p-8 max-h-[90vh] overflow-y-auto">
            <h3 class="text-2xl font-bold font-headline text-slate-900 mb-6">{{ $editing ? 'Edit Claim' : 'New Overtime Claim' }}</h3>
            <form wire:submit="save('draft')" class="space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div class="col-span-2">
                        <label class="block text-sm font-semibold text-slate-700 mb-1">Work Date *</label>
                        <input wire:model="work_date" type="date" class="w-full px-4 py-2 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-primary/20 focus:outline-none"/>
                        @error('work_date') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1">Start Time *</label>
                        <input wire:model.live="start_time" type="time" class="w-full px-4 py-2 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-primary/20 focus:outline-none"/>
                        @error('start_time') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1">End Time *</label>
                        <input wire:model.live="end_time" type="time" class="w-full px-4 py-2 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-primary/20 focus:outline-none"/>
                        @error('end_time') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1">Hourly Rate *</label>
                        <input wire:model.live="hourly_rate" type="number" step="0.01" min="0.01" class="w-full px-4 py-2 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-primary/20 focus:outline-none"/>
                        @error('hourly_rate') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1">Multiplier *</label>
                        <select wire:model.live="multiplier" class="w-full px-4 py-2 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-primary/20 focus:outline-none">
                            <option value="1.0">1.0× (Regular)</option>
                            <option value="1.5">1.5× (Standard OT)</option>
                            <option value="2.0">2.0× (Double Time)</option>
                            <option value="2.5">2.5× (Holiday)</option>
                        </select>
                    </div>
                </div>

                {{-- Live calculation --}}
                @if($computed_hours > 0)
                <div class="flex items-center gap-4 px-4 py-3 rounded-xl bg-amber-50 border border-amber-200">
                    <span class="material-symbols-outlined text-amber-600 text-sm">calculate</span>
                    <div class="text-sm">
                        <span class="font-bold text-amber-700">{{ $computed_hours }}h</span>
                        <span class="text-slate-500"> × rate × {{ $multiplier }} = </span>
                        <span class="font-extrabold text-amber-700">{{ number_format($computed_amount, 2) }}</span>
                    </div>
                </div>
                @endif

                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1">Reason *</label>
                    <textarea wire:model="reason" rows="2" class="w-full px-4 py-2 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-primary/20 focus:outline-none"></textarea>
                    @error('reason') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1">Charge to Account</label>
                    <select wire:model="account_id" class="w-full px-4 py-2 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-primary/20 focus:outline-none">
                        <option value="">— None —</option>
                        @foreach($accounts as $acc)
                            <option value="{{ $acc->id }}">{{ $acc->name }} ({{ number_format((float)$acc->balance, 2) }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="flex gap-3 pt-2">
                    <button type="submit" class="flex-1 bg-slate-100 text-slate-700 py-2.5 rounded-xl font-bold font-headline hover:bg-slate-200 transition-colors">Save Draft</button>
                    <button type="button" wire:click="save('pending')" class="flex-1 bg-primary text-on-primary py-2.5 rounded-xl font-bold font-headline hover:opacity-90 transition-opacity">Submit</button>
                    <button type="button" wire:click="$set('showModal', false)" class="px-5 bg-slate-50 text-slate-500 py-2.5 rounded-xl font-bold font-headline hover:bg-slate-100 transition-colors">Cancel</button>
                </div>
            </form>
        </div>
    </div>
    @endif

    {{-- ── Review Modal ── --}}
    @if($showReviewModal)
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm">
        <div class="bg-white rounded-[2rem] shadow-2xl w-full max-w-sm mx-4 p-8">
            <h3 class="text-2xl font-bold font-headline text-slate-900 mb-4">{{ $review_action === 'approve' ? 'Approve Claim' : 'Reject Claim' }}</h3>
            <form wire:submit="submitReview" class="space-y-4">
                @if($review_action === 'approve')
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1">Approved Amount *</label>
                    <input wire:model="approved_amount" type="number" step="0.01" min="0.01" class="w-full px-4 py-2 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-primary/20 focus:outline-none"/>
                    @error('approved_amount') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                @endif
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1">Notes {{ $review_action === 'reject' ? '*' : '' }}</label>
                    <textarea wire:model="review_notes" rows="3" class="w-full px-4 py-2 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-primary/20 focus:outline-none"></textarea>
                    @error('review_notes') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div class="flex gap-3 pt-2">
                    <button type="submit" class="flex-1 {{ $review_action === 'approve' ? 'bg-green-500' : 'bg-red-500' }} text-white py-2.5 rounded-xl font-bold font-headline hover:opacity-90 transition-opacity">
                        {{ $review_action === 'approve' ? 'Approve' : 'Reject' }}
                    </button>
                    <button type="button" wire:click="$set('showReviewModal', false)" class="flex-1 bg-slate-100 text-slate-700 py-2.5 rounded-xl font-bold font-headline hover:bg-slate-200 transition-colors">Cancel</button>
                </div>
            </form>
        </div>
    </div>
    @endif
</div>

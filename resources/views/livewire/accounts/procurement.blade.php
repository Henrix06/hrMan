<div>
    @if(session('message'))
        <div class="mb-6 px-4 py-3 rounded-xl bg-green-50 border border-green-200 text-green-700 text-sm flex items-center gap-2">
            <span class="material-symbols-outlined text-sm">check_circle</span>
            {{ session('message') }}
        </div>
    @endif
    @if(session('error'))
        <div class="mb-6 px-4 py-3 rounded-xl bg-red-50 border border-red-200 text-red-700 text-sm flex items-center gap-2">
            <span class="material-symbols-outlined text-sm">error</span>
            {{ session('error') }}
        </div>
    @endif

    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
        <div>
            <h2 class="text-2xl md:text-3xl font-extrabold text-slate-900 font-headline">Procurement</h2>
            <p class="text-slate-500 text-sm mt-1">Purchase requests and approvals</p>
        </div>
        <button wire:click="openCreate"
            class="bg-gradient-to-br from-primary to-primary-container text-on-primary px-5 py-2.5 rounded-xl font-bold font-headline shadow-lg shadow-primary/20 hover:opacity-90 transition-opacity flex items-center justify-center gap-2 w-full sm:w-auto">
            <span class="material-symbols-outlined text-sm">add</span> New Request
        </button>
    </div>

    {{-- Filters --}}
    <div class="glass-card rounded-[1.5rem] p-4 mb-6 flex flex-wrap gap-3">
        <input wire:model.live.debounce.300ms="search" type="text" placeholder="Search title, reference, vendor..."
            class="px-4 py-2 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-primary/20 focus:outline-none flex-1 min-w-48"/>
        <select wire:model.live="statusFilter" class="px-4 py-2 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-primary/20 focus:outline-none">
            <option value="">All Statuses</option>
            <option value="draft">Draft</option>
            <option value="pending">Pending</option>
            <option value="approved">Approved</option>
            <option value="rejected">Rejected</option>
            <option value="paid">Paid</option>
            <option value="cancelled">Cancelled</option>
        </select>
    </div>

    {{-- Table --}}
    <div class="glass-card rounded-[1.5rem] overflow-hidden">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-slate-100 text-left">
                    <th class="px-5 py-4 font-bold text-slate-600 text-xs uppercase tracking-widest">Request</th>
                    <th class="px-5 py-4 font-bold text-slate-600 text-xs uppercase tracking-widest">Category</th>
                    <th class="px-5 py-4 font-bold text-slate-600 text-xs uppercase tracking-widest">Amount</th>
                    <th class="px-5 py-4 font-bold text-slate-600 text-xs uppercase tracking-widest">Account</th>
                    <th class="px-5 py-4 font-bold text-slate-600 text-xs uppercase tracking-widest">Status</th>
                    <th class="px-5 py-4 font-bold text-slate-600 text-xs uppercase tracking-widest">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-50">
                @forelse($query->paginate(15) as $req)
                @php
                    $statusColors = [
                        'draft'     => 'bg-slate-100 text-slate-500',
                        'pending'   => 'bg-amber-100 text-amber-700',
                        'approved'  => 'bg-green-100 text-green-700',
                        'rejected'  => 'bg-red-100 text-red-700',
                        'paid'      => 'bg-blue-100 text-blue-700',
                        'cancelled' => 'bg-slate-100 text-slate-400',
                    ];
                    $priorityColors = ['urgent'=>'text-red-600','high'=>'text-orange-500','medium'=>'text-amber-500','low'=>'text-slate-400'];
                @endphp
                <tr class="hover:bg-slate-50/50 transition-colors">
                    <td class="px-5 py-4">
                        <p class="font-bold text-slate-800">{{ $req->title }}</p>
                        <p class="text-xs text-slate-400 mt-0.5">{{ $req->reference }} · {{ $req->requester->name }}</p>
                        @if($req->vendor) <p class="text-xs text-slate-400">Vendor: {{ $req->vendor }}</p> @endif
                    </td>
                    <td class="px-5 py-4">
                        <span class="capitalize text-slate-600">{{ $req->category }}</span>
                        <p class="text-xs {{ $priorityColors[$req->priority] }} font-semibold capitalize">{{ $req->priority }}</p>
                    </td>
                    <td class="px-5 py-4">
                        <p class="font-bold text-slate-800">{{ number_format((float)$req->estimated_cost, 2) }}</p>
                        @if($req->approved_amount)
                            <p class="text-xs text-green-600 font-semibold">Approved: {{ number_format((float)$req->approved_amount, 2) }}</p>
                        @endif
                    </td>
                    <td class="px-5 py-4 text-slate-500 text-xs">
                        {{ $req->account?->name ?? '—' }}
                    </td>
                    <td class="px-5 py-4">
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-widest {{ $statusColors[$req->status] }}">{{ $req->status }}</span>
                        @if($req->reviewer && $req->reviewed_at)
                            <p class="text-xs text-slate-400 mt-0.5">by {{ $req->reviewer->name }}</p>
                        @endif
                    </td>
                    <td class="px-5 py-4">
                        <div class="flex items-center gap-2">
                            @if($req->isDraft())
                                <button wire:click="openEdit({{ $req->id }})" class="text-slate-400 hover:text-primary transition-colors" title="Edit">
                                    <span class="material-symbols-outlined text-sm">edit</span>
                                </button>
                                <button wire:click="save('pending')" class="text-slate-400 hover:text-amber-500 transition-colors" title="Submit for approval">
                                    <span class="material-symbols-outlined text-sm">send</span>
                                </button>
                            @endif
                            @if($canReview && $req->isPending())
                                <button wire:click="openReview({{ $req->id }}, 'approve')" class="text-slate-400 hover:text-green-600 transition-colors" title="Approve">
                                    <span class="material-symbols-outlined text-sm">check_circle</span>
                                </button>
                                <button wire:click="openReview({{ $req->id }}, 'reject')" class="text-slate-400 hover:text-red-500 transition-colors" title="Reject">
                                    <span class="material-symbols-outlined text-sm">cancel</span>
                                </button>
                            @endif
                            @if(in_array($req->status, ['draft','pending']))
                                <button wire:click="cancel({{ $req->id }})" wire:confirm="Cancel this request?" class="text-slate-400 hover:text-error transition-colors" title="Cancel">
                                    <span class="material-symbols-outlined text-sm">delete</span>
                                </button>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="py-20 text-center text-slate-400">
                        <span class="material-symbols-outlined text-5xl block mb-3">shopping_cart</span>
                        <p>No procurement requests found.</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $query->paginate(15)->links() }}</div>

    {{-- ── Create/Edit Modal ── --}}
    @if($showModal)
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm">
        <div class="bg-white rounded-[2rem] shadow-2xl w-full max-w-lg mx-4 p-8 max-h-[90vh] overflow-y-auto">
            <h3 class="text-2xl font-bold font-headline text-slate-900 mb-6">{{ $editing ? 'Edit Request' : 'New Procurement Request' }}</h3>
            <form wire:submit="save('draft')" class="space-y-4">
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1">Title *</label>
                    <input wire:model="title" type="text" class="w-full px-4 py-2 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-primary/20 focus:outline-none"/>
                    @error('title') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1">Description *</label>
                    <textarea wire:model="description" rows="3" class="w-full px-4 py-2 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-primary/20 focus:outline-none"></textarea>
                    @error('description') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1">Category *</label>
                        <select wire:model="category" class="w-full px-4 py-2 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-primary/20 focus:outline-none">
                            <option value="supplies">Supplies</option>
                            <option value="equipment">Equipment</option>
                            <option value="services">Services</option>
                            <option value="software">Software</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1">Priority</label>
                        <select wire:model="priority" class="w-full px-4 py-2 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-primary/20 focus:outline-none">
                            <option value="low">Low</option>
                            <option value="medium">Medium</option>
                            <option value="high">High</option>
                            <option value="urgent">Urgent</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1">Estimated Cost *</label>
                        <input wire:model="estimated_cost" type="number" step="0.01" min="0.01" class="w-full px-4 py-2 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-primary/20 focus:outline-none"/>
                        @error('estimated_cost') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1">Required By</label>
                        <input wire:model="required_by" type="date" class="w-full px-4 py-2 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-primary/20 focus:outline-none"/>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1">Vendor</label>
                        <input wire:model="vendor" type="text" class="w-full px-4 py-2 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-primary/20 focus:outline-none"/>
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
                </div>
                <div class="flex gap-3 pt-2">
                    <button type="submit" class="flex-1 bg-slate-100 text-slate-700 py-2.5 rounded-xl font-bold font-headline hover:bg-slate-200 transition-colors">Save Draft</button>
                    <button type="button" wire:click="save('pending')" class="flex-1 bg-primary text-on-primary py-2.5 rounded-xl font-bold font-headline hover:opacity-90 transition-opacity">Submit for Approval</button>
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
            <h3 class="text-2xl font-bold font-headline text-slate-900 mb-2">{{ $review_action === 'approve' ? 'Approve Request' : 'Reject Request' }}</h3>
            <form wire:submit="submitReview" class="space-y-4 mt-4">
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

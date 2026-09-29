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
            <h2 class="text-2xl md:text-3xl font-extrabold text-slate-900 font-headline">Transport Requests</h2>
            <p class="text-slate-500 text-sm mt-1">Get the items you sold to the customer</p>
        </div>
        <button wire:click="openCreate"
            class="bg-gradient-to-br from-primary to-primary-container text-on-primary px-5 py-2.5 rounded-xl font-bold font-headline shadow-lg shadow-primary/20 hover:opacity-90 transition-opacity flex items-center justify-center gap-2 w-full sm:w-auto">
            <span class="material-symbols-outlined text-sm">local_shipping</span> Request Transport
        </button>
    </div>

    <div class="glass-card rounded-[1.5rem] p-4 mb-6 flex flex-wrap gap-3">
        <input wire:model.live.debounce.300ms="search" type="text" placeholder="Search reference, order or destination..."
            class="px-4 py-2 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-primary/20 focus:outline-none flex-1 min-w-48"/>
        <select wire:model.live="statusFilter" class="px-4 py-2 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-primary/20 focus:outline-none">
            <option value="">All Statuses</option>
            <option value="pending">Pending</option>
            <option value="approved">Approved</option>
            <option value="in_transit">In Transit</option>
            <option value="delivered">Delivered</option>
            <option value="cancelled">Cancelled</option>
        </select>
    </div>

    <div class="glass-card rounded-[1.5rem] overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-slate-100 text-left">
                        <th class="px-5 py-4 font-bold text-slate-600 text-xs uppercase tracking-widest">Request</th>
                        <th class="px-5 py-4 font-bold text-slate-600 text-xs uppercase tracking-widest">Route</th>
                        <th class="px-5 py-4 font-bold text-slate-600 text-xs uppercase tracking-widest">Items</th>
                        <th class="px-5 py-4 font-bold text-slate-600 text-xs uppercase tracking-widest">Trip</th>
                        <th class="px-5 py-4 font-bold text-slate-600 text-xs uppercase tracking-widest">Status</th>
                        <th class="px-5 py-4 font-bold text-slate-600 text-xs uppercase tracking-widest">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    @forelse($requests as $request)
                    @php
                        $statusColors = ['pending'=>'bg-amber-100 text-amber-700','approved'=>'bg-blue-100 text-blue-700','in_transit'=>'bg-indigo-100 text-indigo-700','delivered'=>'bg-green-100 text-green-700','cancelled'=>'bg-red-100 text-red-700'];
                        $canCancel = in_array($request->status, ['pending', 'approved'], true) && ($request->requested_by === auth()->id() || $canManageTransport);
                    @endphp
                    <tr class="hover:bg-slate-50/50 transition-colors">
                        <td class="px-5 py-4">
                            <p class="font-bold text-slate-800">{{ $request->reference }}</p>
                            <p class="text-xs text-slate-400">
                                {{ $request->order?->reference ?? 'No order' }} · by {{ $request->requester?->name ?? '—' }}
                            </p>
                        </td>
                        <td class="px-5 py-4">
                            <p class="text-slate-700 font-semibold">{{ $request->pickup_address }} → {{ $request->delivery_address }}</p>
                            @if($request->pickup_contact || $request->delivery_contact)
                                <p class="text-xs text-slate-400">{{ $request->pickup_contact ?: '—' }} / {{ $request->delivery_contact ?: '—' }}</p>
                            @endif
                        </td>
                        <td class="px-5 py-4 text-slate-600">
                            <p class="text-xs text-slate-500 truncate max-w-[200px]">{{ $request->items_summary ?: '—' }}</p>
                        </td>
                        <td class="px-5 py-4">
                            @if($request->scheduled_date)
                                <p class="text-slate-700">{{ $request->scheduled_date->format('M d, Y') }}</p>
                            @else
                                <p class="text-xs text-slate-400">Unscheduled</p>
                            @endif
                            <p class="text-xs text-slate-400">
                                {{ $request->driver?->name ?? 'No driver' }}@if($request->vehicle) · {{ $request->vehicle }}@endif
                            </p>
                        </td>
                        <td class="px-5 py-4">
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-widest {{ $statusColors[$request->status] }}">{{ str_replace('_', ' ', $request->status) }}</span>
                            @if($request->delivered_at)
                                <p class="text-xs text-slate-400 mt-1">{{ $request->delivered_at->diffForHumans() }}</p>
                            @endif
                        </td>
                        <td class="px-5 py-4">
                            <div class="flex items-center gap-2">
                                @if($canManageTransport && $request->status === 'pending')
                                <button wire:click="openApprove({{ $request->id }})" class="text-slate-400 hover:text-green-600 transition-colors" title="Approve and assign">
                                    <span class="material-symbols-outlined text-sm">check_circle</span>
                                </button>
                                @endif

                                @if($canManageTransport && $request->status === 'approved')
                                <button wire:click="markInTransit({{ $request->id }})" class="text-slate-400 hover:text-indigo-600 transition-colors" title="Mark in transit">
                                    <span class="material-symbols-outlined text-sm">local_shipping</span>
                                </button>
                                @endif

                                @if($canManageTransport && $request->status === 'in_transit')
                                <button wire:click="deliver({{ $request->id }})" class="text-slate-400 hover:text-green-600 transition-colors" title="Mark delivered">
                                    <span class="material-symbols-outlined text-sm">where_to_vote</span>
                                </button>
                                @endif

                                @if($canCancel)
                                <button wire:click="cancel({{ $request->id }})" wire:confirm="Cancel this transport request?" class="text-slate-400 hover:text-red-500 transition-colors" title="Cancel">
                                    <span class="material-symbols-outlined text-sm">cancel</span>
                                </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="py-20 text-center text-slate-400">
                            <span class="material-symbols-outlined text-5xl block mb-3">local_shipping</span>
                            <p>No transport requests found.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="mt-4">{{ $requests->links() }}</div>

    {{-- ── New Request Modal ── --}}
    @if($showModal)
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm p-4">
        <div class="bg-white rounded-[2rem] shadow-2xl w-full max-w-lg p-6 md:p-8 max-h-[90vh] overflow-y-auto">
            <h3 class="text-2xl font-bold font-headline text-slate-900 mb-6">Request Transport</h3>

            @if($requestableOrders->isEmpty())
                <div class="px-4 py-3 rounded-xl bg-amber-50 border border-amber-200 text-amber-700 text-sm mb-4">
                    You have no confirmed orders waiting to be moved. Log an order and confirm it first.
                </div>
            @else
            <form wire:submit="requestTransport" class="space-y-4">
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1">Sales Order *</label>
                    <select wire:model.live="order_id" class="w-full px-4 py-2 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-primary/20 focus:outline-none">
                        <option value="">— Choose an order —</option>
                        @foreach($requestableOrders as $order)
                        <option value="{{ $order->id }}">{{ $order->reference }} · {{ $order->customer_name }} ({{ $order->items->count() }} items)</option>
                        @endforeach
                    </select>
                    @error('order_id') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1">Collect From *</label>
                        <input wire:model="pickup_address" type="text" class="w-full px-4 py-2 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-primary/20 focus:outline-none"/>
                        @error('pickup_address') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1">Deliver To *</label>
                        <input wire:model="delivery_address" type="text" class="w-full px-4 py-2 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-primary/20 focus:outline-none"/>
                        @error('delivery_address') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1">Pickup Contact</label>
                        <input wire:model="pickup_contact" type="text" class="w-full px-4 py-2 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-primary/20 focus:outline-none"/>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1">Delivery Contact</label>
                        <input wire:model="delivery_contact" type="text" class="w-full px-4 py-2 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-primary/20 focus:outline-none"/>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1">What Is Being Moved</label>
                    <input wire:model="items_summary" type="text" class="w-full px-4 py-2 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-primary/20 focus:outline-none"/>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1">Preferred Date</label>
                    <input wire:model="scheduled_date" type="date" class="w-full px-4 py-2 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-primary/20 focus:outline-none"/>
                    @error('scheduled_date') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1">Notes</label>
                    <textarea wire:model="transport_notes" rows="2" class="w-full px-4 py-2 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-primary/20 focus:outline-none"></textarea>
                </div>

                <div class="flex gap-3 pt-2">
                    <button type="submit" class="flex-1 bg-primary text-on-primary py-2.5 rounded-xl font-bold font-headline hover:opacity-90 transition-opacity">Submit Request</button>
                    <button type="button" wire:click="$set('showModal', false)" class="flex-1 bg-slate-100 text-slate-700 py-2.5 rounded-xl font-bold font-headline hover:bg-slate-200 transition-colors">Cancel</button>
                </div>
            </form>
            @endif
        </div>
    </div>
    @endif

    {{-- ── Approve Modal ── --}}
    @if($showApproveModal)
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm p-4">
        <div class="bg-white rounded-[2rem] shadow-2xl w-full max-w-md p-6 md:p-8">
            <h3 class="text-2xl font-bold font-headline text-slate-900 mb-6">Approve Transport</h3>
            <form wire:submit="submitApproval" class="space-y-4">
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1">Driver *</label>
                    <select wire:model="driver_id" class="w-full px-4 py-2 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-primary/20 focus:outline-none">
                        <option value="">— Choose a driver —</option>
                        @foreach($drivers as $driver)
                        <option value="{{ $driver->id }}">{{ $driver->name }}</option>
                        @endforeach
                    </select>
                    @error('driver_id') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1">Vehicle</label>
                    <input wire:model="vehicle" type="text" placeholder="e.g. Truck ABC 1234" class="w-full px-4 py-2 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-primary/20 focus:outline-none"/>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1">Scheduled Date</label>
                    <input wire:model="approve_scheduled_date" type="date" class="w-full px-4 py-2 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-primary/20 focus:outline-none"/>
                    @error('approve_scheduled_date') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1">Notes</label>
                    <textarea wire:model="approve_notes" rows="2" class="w-full px-4 py-2 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-primary/20 focus:outline-none"></textarea>
                </div>
                <div class="flex gap-3 pt-2">
                    <button type="submit" class="flex-1 bg-green-500 text-white py-2.5 rounded-xl font-bold font-headline hover:opacity-90 transition-opacity">Approve</button>
                    <button type="button" wire:click="$set('showApproveModal', false)" class="flex-1 bg-slate-100 text-slate-700 py-2.5 rounded-xl font-bold font-headline hover:bg-slate-200 transition-colors">Cancel</button>
                </div>
            </form>
        </div>
    </div>
    @endif
</div>

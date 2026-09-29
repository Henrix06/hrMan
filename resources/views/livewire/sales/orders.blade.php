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
            <h2 class="text-2xl md:text-3xl font-extrabold text-slate-900 font-headline">Sales Orders</h2>
            <p class="text-slate-500 text-sm mt-1">Log what you sold, then arrange to get it to the customer</p>
        </div>
        <button wire:click="openCreate"
            class="bg-gradient-to-br from-primary to-primary-container text-on-primary px-5 py-2.5 rounded-xl font-bold font-headline shadow-lg shadow-primary/20 hover:opacity-90 transition-opacity flex items-center justify-center gap-2 w-full sm:w-auto">
            <span class="material-symbols-outlined text-sm">add</span> New Order
        </button>
    </div>

    {{-- Stats --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
        <div class="glass-card rounded-[1.5rem] p-5">
            <p class="text-xs font-bold uppercase tracking-widest text-slate-400">Confirmed sales this month</p>
            <p class="text-2xl font-extrabold font-headline text-slate-900 mt-1">{{ $currency }} {{ number_format($stats['month_value'], 2) }}</p>
        </div>
        <div class="glass-card rounded-[1.5rem] p-5">
            <p class="text-xs font-bold uppercase tracking-widest text-slate-400">Orders this month</p>
            <p class="text-2xl font-extrabold font-headline text-slate-900 mt-1">{{ $stats['month_count'] }}</p>
        </div>
        <div class="glass-card rounded-[1.5rem] p-5">
            <p class="text-xs font-bold uppercase tracking-widest text-slate-400">Transport in progress</p>
            <p class="text-2xl font-extrabold font-headline text-slate-900 mt-1">{{ $stats['open_transport'] }}</p>
        </div>
    </div>

    {{-- Filters --}}
    <div class="glass-card rounded-[1.5rem] p-4 mb-6 flex flex-wrap gap-3">
        <input wire:model.live.debounce.300ms="search" type="text" placeholder="Search reference or customer..."
            class="px-4 py-2 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-primary/20 focus:outline-none flex-1 min-w-48"/>
        <select wire:model.live="statusFilter" class="px-4 py-2 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-primary/20 focus:outline-none">
            <option value="">All Statuses</option>
            <option value="draft">Draft</option>
            <option value="confirmed">Confirmed</option>
            <option value="fulfilled">Fulfilled</option>
            <option value="cancelled">Cancelled</option>
        </select>
    </div>

    {{-- Orders --}}
    <div class="glass-card rounded-[1.5rem] overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-slate-100 text-left">
                        <th class="px-5 py-4 font-bold text-slate-600 text-xs uppercase tracking-widest">Order</th>
                        <th class="px-5 py-4 font-bold text-slate-600 text-xs uppercase tracking-widest">Logged By</th>
                        <th class="px-5 py-4 font-bold text-slate-600 text-xs uppercase tracking-widest">Items</th>
                        <th class="px-5 py-4 font-bold text-slate-600 text-xs uppercase tracking-widest">Total</th>
                        <th class="px-5 py-4 font-bold text-slate-600 text-xs uppercase tracking-widest">Transport</th>
                        <th class="px-5 py-4 font-bold text-slate-600 text-xs uppercase tracking-widest">Status</th>
                        <th class="px-5 py-4 font-bold text-slate-600 text-xs uppercase tracking-widest">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    @forelse($orders as $order)
                    @php
                        $statusColors = ['draft'=>'bg-slate-100 text-slate-500','confirmed'=>'bg-blue-100 text-blue-700','fulfilled'=>'bg-green-100 text-green-700','cancelled'=>'bg-red-100 text-red-700'];
                        $isOwner = $order->sold_by === auth()->id();
                        $canTouch = $isOwner || $canManageSales;
                        $openTransport = $order->transportRequests->first(fn ($t) => in_array($t->status, ['pending','approved','in_transit'], true));
                        $deliveredTransport = $order->transportRequests->firstWhere('status', 'delivered');
                    @endphp
                    <tr class="hover:bg-slate-50/50 transition-colors">
                        <td class="px-5 py-4">
                            <button wire:click="view({{ $order->id }})" class="text-left">
                                <p class="font-bold text-slate-800 hover:text-primary transition-colors">{{ $order->customer_name }}</p>
                                <p class="text-xs text-slate-400">{{ $order->reference }} · {{ $order->order_date->format('M d, Y') }}</p>
                            </button>
                        </td>
                        <td class="px-5 py-4 text-slate-600">{{ $order->seller?->name ?? '—' }}</td>
                        <td class="px-5 py-4 text-slate-600">
                            {{ $order->items->count() }}
                            <p class="text-xs text-slate-400 truncate max-w-[200px]">{{ $order->itemsSummary() }}</p>
                        </td>
                        <td class="px-5 py-4">
                            <p class="font-bold text-slate-800">{{ $order->currency }} {{ number_format((float) $order->total_amount, 2) }}</p>
                            @if((float) $order->tax_amount > 0)
                                <p class="text-xs text-slate-400">incl. {{ number_format((float) $order->tax_amount, 2) }} tax</p>
                            @endif
                        </td>
                        <td class="px-5 py-4">
                            @if($openTransport)
                                <span class="flex items-center gap-1 w-fit px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-widest bg-amber-100 text-amber-700">
                                    <span class="material-symbols-outlined text-[11px]">local_shipping</span>
                                    {{ str_replace('_', ' ', $openTransport->status) }}
                                </span>
                            @elseif($deliveredTransport)
                                <span class="flex items-center gap-1 w-fit px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-widest bg-green-100 text-green-700">
                                    <span class="material-symbols-outlined text-[11px]">check_circle</span> delivered
                                </span>
                            @else
                                <span class="text-xs text-slate-400">Not arranged</span>
                            @endif
                        </td>
                        <td class="px-5 py-4">
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-widest {{ $statusColors[$order->status] }}">{{ $order->status }}</span>
                        </td>
                        <td class="px-5 py-4">
                            <div class="flex items-center gap-2">
                                <button wire:click="view({{ $order->id }})" class="text-slate-400 hover:text-primary transition-colors" title="View">
                                    <span class="material-symbols-outlined text-sm">visibility</span>
                                </button>

                                <a href="{{ route('sales.orders.document', $order) }}" target="_blank" rel="noopener"
                                    class="text-slate-400 hover:text-primary transition-colors" title="Print / save as PDF">
                                    <span class="material-symbols-outlined text-sm">print</span>
                                </a>

                                @if($order->status === 'draft' && $canTouch)
                                <button wire:click="openEdit({{ $order->id }})" class="text-slate-400 hover:text-primary transition-colors" title="Edit">
                                    <span class="material-symbols-outlined text-sm">edit</span>
                                </button>
                                @endif

                                @if($canManageSales && $order->status === 'draft')
                                <button wire:click="confirm({{ $order->id }})" class="text-slate-400 hover:text-blue-600 transition-colors" title="Confirm order">
                                    <span class="material-symbols-outlined text-sm">check_circle</span>
                                </button>
                                @endif

                                @if($canManageSales && $order->status === 'confirmed')
                                <button wire:click="fulfill({{ $order->id }})" class="text-slate-400 hover:text-green-600 transition-colors" title="Mark fulfilled">
                                    <span class="material-symbols-outlined text-sm">local_shipping</span>
                                </button>
                                @endif

                                @if(in_array($order->status, ['confirmed', 'fulfilled'], true) && $canTouch && !$openTransport)
                                <a href="{{ route('sales.transport', ['order' => $order->id]) }}" wire:navigate
                                    class="text-slate-400 hover:text-primary transition-colors" title="Request transport">
                                    <span class="material-symbols-outlined text-sm">add_road</span>
                                </a>
                                @endif

                                @if(in_array($order->status, ['draft', 'confirmed'], true) && $canTouch)
                                <button wire:click="cancel({{ $order->id }})" wire:confirm="Cancel this order?" class="text-slate-400 hover:text-red-500 transition-colors" title="Cancel">
                                    <span class="material-symbols-outlined text-sm">cancel</span>
                                </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="py-20 text-center text-slate-400">
                            <span class="material-symbols-outlined text-5xl block mb-3">point_of_sale</span>
                            <p>No sales orders found.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="mt-4">{{ $orders->links() }}</div>

    {{-- ── Create/Edit Modal ── --}}
    @if($showModal)
    @php $totals = $this->draftTotals(); @endphp
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm p-4">
        <div class="bg-white rounded-[2rem] shadow-2xl w-full max-w-2xl p-6 md:p-8 max-h-[90vh] overflow-y-auto">
            <h3 class="text-2xl font-bold font-headline text-slate-900 mb-6">{{ $editing ? 'Edit Order' : 'New Sales Order' }}</h3>
            <form wire:submit="save('draft')" class="space-y-5">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1">Customer *</label>
                        <input wire:model="customer_name" type="text" class="w-full px-4 py-2 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-primary/20 focus:outline-none"/>
                        @error('customer_name') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1">Order Date *</label>
                        <input wire:model="order_date" type="date" class="w-full px-4 py-2 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-primary/20 focus:outline-none"/>
                        @error('order_date') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1">Email</label>
                        <input wire:model="customer_email" type="email" class="w-full px-4 py-2 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-primary/20 focus:outline-none"/>
                        @error('customer_email') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1">Phone</label>
                        <input wire:model="customer_phone" type="text" class="w-full px-4 py-2 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-primary/20 focus:outline-none"/>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1">Customer Address</label>
                        <input wire:model="customer_address" type="text" placeholder="Used as the pickup point" class="w-full px-4 py-2 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-primary/20 focus:outline-none"/>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1">Delivery Address</label>
                        <input wire:model="delivery_address" type="text" placeholder="Where the goods must go" class="w-full px-4 py-2 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-primary/20 focus:outline-none"/>
                    </div>
                </div>

                {{-- Line items --}}
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <label class="text-sm font-semibold text-slate-700">Line Items *</label>
                        <button type="button" wire:click="addItemRow" class="text-xs font-bold text-primary hover:underline flex items-center gap-1">
                            <span class="material-symbols-outlined text-[14px]">add</span> Add item
                        </button>
                    </div>
                    <div class="space-y-2">
                        @foreach($items as $index => $item)
                        <div class="grid grid-cols-12 gap-2 items-start" wire:key="item-{{ $index }}">
                            <div class="col-span-5">
                                <input wire:model.live="items.{{ $index }}.description" type="text" placeholder="Item or service"
                                    class="w-full px-3 py-2 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-primary/20 focus:outline-none"/>
                                @error("items.{$index}.description") <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                            </div>
                            <div class="col-span-2">
                                <input wire:model.live="items.{{ $index }}.quantity" type="number" step="0.01" min="0.01" placeholder="Qty"
                                    class="w-full px-3 py-2 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-primary/20 focus:outline-none"/>
                                @error("items.{$index}.quantity") <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                            </div>
                            <div class="col-span-3">
                                <input wire:model.live="items.{{ $index }}.unit_price" type="number" step="0.01" min="0" placeholder="Unit price"
                                    class="w-full px-3 py-2 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-primary/20 focus:outline-none"/>
                                @error("items.{$index}.unit_price") <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                            </div>
                            <div class="col-span-1 pt-2 text-right text-xs font-semibold text-slate-500">
                                {{ number_format((float) ($item['quantity'] ?? 0) * (float) ($item['unit_price'] ?? 0), 2) }}
                            </div>
                            <div class="col-span-1 pt-1.5 text-right">
                                <button type="button" wire:click="removeItemRow({{ $index }})" class="text-slate-300 hover:text-red-500 transition-colors">
                                    <span class="material-symbols-outlined text-sm">remove_circle</span>
                                </button>
                            </div>
                        </div>
                        @endforeach
                    </div>
                    @error('items') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1">Tax Rate (%)</label>
                        <input wire:model.live="tax_rate" type="number" step="0.01" min="0" max="100" class="w-full px-4 py-2 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-primary/20 focus:outline-none"/>
                        @error('tax_rate') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div class="sm:col-span-2 bg-slate-50 rounded-xl p-4">
                        <div class="flex items-center justify-between text-sm text-slate-500">
                            <span>Subtotal</span><span>{{ number_format($totals['subtotal'], 2) }}</span>
                        </div>
                        <div class="flex items-center justify-between text-sm text-slate-500">
                            <span>Tax</span><span>{{ number_format($totals['tax_amount'], 2) }}</span>
                        </div>
                        <div class="flex items-center justify-between text-base font-bold text-slate-800 mt-1 pt-1 border-t border-slate-200">
                            <span>Total</span><span>{{ $currency }} {{ number_format($totals['total_amount'], 2) }}</span>
                        </div>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1">Notes</label>
                    <textarea wire:model="order_notes" rows="2" class="w-full px-4 py-2 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-primary/20 focus:outline-none"></textarea>
                </div>

                <div class="flex flex-wrap gap-3 pt-2">
                    <button type="submit" class="flex-1 bg-slate-100 text-slate-700 py-2.5 rounded-xl font-bold font-headline hover:bg-slate-200 transition-colors min-w-32">Save Draft</button>
                    <button type="button" wire:click="save('confirmed')" class="flex-1 bg-primary text-on-primary py-2.5 rounded-xl font-bold font-headline hover:opacity-90 transition-opacity min-w-32">Log &amp; Confirm</button>
                    <button type="button" wire:click="$set('showModal', false)" class="px-5 bg-slate-50 text-slate-500 py-2.5 rounded-xl font-bold font-headline hover:bg-slate-100 transition-colors">Cancel</button>
                </div>
            </form>
        </div>
    </div>
    @endif

    {{-- ── View Modal ── --}}
    @if($showView && $activeOrder)
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm p-4">
        <div class="bg-white rounded-[2rem] shadow-2xl w-full max-w-2xl p-6 md:p-8 max-h-[90vh] overflow-y-auto">
            @php $statusColors = ['draft'=>'bg-slate-100 text-slate-500','confirmed'=>'bg-blue-100 text-blue-700','fulfilled'=>'bg-green-100 text-green-700','cancelled'=>'bg-red-100 text-red-700']; @endphp
            <div class="flex items-start justify-between gap-4 mb-5">
                <div>
                    <div class="flex items-center gap-2 mb-1">
                        <h3 class="text-2xl font-bold font-headline text-slate-900">{{ $activeOrder->customer_name }}</h3>
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-widest {{ $statusColors[$activeOrder->status] }}">{{ $activeOrder->status }}</span>
                    </div>
                    <p class="text-sm text-slate-500">
                        {{ $activeOrder->reference }} · {{ $activeOrder->order_date->format('M d, Y') }} · logged by {{ $activeOrder->seller?->name ?? '—' }}
                    </p>
                </div>
                <div class="flex items-center gap-2 flex-shrink-0">
                    <a href="{{ route('sales.orders.document', $activeOrder) }}" target="_blank" rel="noopener"
                        class="flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-slate-100 text-slate-600 text-xs font-bold hover:bg-slate-200 transition-colors">
                        <span class="material-symbols-outlined text-[14px]">print</span> Print
                    </a>
                    <button wire:click="$set('showView', false)" class="p-1 rounded-lg text-slate-400 hover:text-slate-700 hover:bg-slate-100 transition-colors">
                        <span class="material-symbols-outlined">close</span>
                    </button>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-5 text-sm">
                <div class="bg-slate-50 rounded-xl p-4">
                    <p class="text-xs font-bold uppercase tracking-widest text-slate-400 mb-1">Customer</p>
                    <p class="text-slate-700">{{ $activeOrder->customer_email ?: '—' }}</p>
                    <p class="text-slate-700">{{ $activeOrder->customer_phone ?: '' }}</p>
                    <p class="text-slate-500 text-xs mt-1">{{ $activeOrder->customer_address ?: 'No address on file' }}</p>
                </div>
                <div class="bg-slate-50 rounded-xl p-4">
                    <p class="text-xs font-bold uppercase tracking-widest text-slate-400 mb-1">Deliver To</p>
                    <p class="text-slate-700">{{ $activeOrder->delivery_address ?: 'Not specified' }}</p>
                </div>
            </div>

            <h4 class="text-xs font-bold uppercase tracking-widest text-slate-400 mb-2">Items</h4>
            <div class="rounded-xl border border-slate-100 overflow-hidden mb-5">
                <table class="w-full text-sm">
                    <thead class="bg-slate-50">
                        <tr class="text-left">
                            <th class="px-4 py-2 font-bold text-slate-600 text-xs uppercase tracking-widest">Description</th>
                            <th class="px-4 py-2 font-bold text-slate-600 text-xs uppercase tracking-widest text-right">Qty</th>
                            <th class="px-4 py-2 font-bold text-slate-600 text-xs uppercase tracking-widest text-right">Unit</th>
                            <th class="px-4 py-2 font-bold text-slate-600 text-xs uppercase tracking-widest text-right">Line</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        @foreach($activeOrder->items as $item)
                        <tr>
                            <td class="px-4 py-2 text-slate-700">{{ $item->description }}</td>
                            <td class="px-4 py-2 text-slate-600 text-right">{{ rtrim(rtrim(number_format((float) $item->quantity, 2), '0'), '.') }}</td>
                            <td class="px-4 py-2 text-slate-600 text-right">{{ number_format((float) $item->unit_price, 2) }}</td>
                            <td class="px-4 py-2 text-slate-800 font-semibold text-right">{{ number_format((float) $item->line_total, 2) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="flex flex-col items-end gap-1 text-sm mb-5">
                <div class="flex gap-8 text-slate-500"><span>Subtotal</span><span>{{ number_format((float) $activeOrder->subtotal, 2) }}</span></div>
                <div class="flex gap-8 text-slate-500"><span>Tax ({{ rtrim(rtrim(number_format((float) $activeOrder->tax_rate, 2), '0'), '.') }}%)</span><span>{{ number_format((float) $activeOrder->tax_amount, 2) }}</span></div>
                <div class="flex gap-8 text-base font-bold text-slate-800"><span>Total</span><span>{{ $activeOrder->currency }} {{ number_format((float) $activeOrder->total_amount, 2) }}</span></div>
            </div>

            <h4 class="text-xs font-bold uppercase tracking-widest text-slate-400 mb-2">Transport</h4>
            <div class="space-y-2">
                @forelse($activeOrder->transportRequests as $request)
                @php $transportColors = ['pending'=>'bg-amber-100 text-amber-700','approved'=>'bg-blue-100 text-blue-700','in_transit'=>'bg-indigo-100 text-indigo-700','delivered'=>'bg-green-100 text-green-700','cancelled'=>'bg-red-100 text-red-700']; @endphp
                <div class="flex items-center justify-between gap-3 px-4 py-2.5 rounded-xl bg-slate-50">
                    <div class="min-w-0">
                        <p class="text-sm font-semibold text-slate-700">{{ $request->reference }} · {{ $request->pickup_address }} → {{ $request->delivery_address }}</p>
                        <p class="text-xs text-slate-400">
                            {{ $request->driver?->name ? 'Driver: '.$request->driver->name : 'No driver assigned' }}
                            @if($request->scheduled_date) · {{ $request->scheduled_date->format('M d, Y') }} @endif
                        </p>
                    </div>
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-widest flex-shrink-0 {{ $transportColors[$request->status] }}">{{ str_replace('_', ' ', $request->status) }}</span>
                </div>
                @empty
                <p class="text-xs text-slate-400">No transport has been arranged for this order yet.</p>
                @endforelse
            </div>

            @if($activeOrder->notes)
            <p class="text-sm text-slate-500 bg-slate-50 rounded-xl p-4 mt-5">{{ $activeOrder->notes }}</p>
            @endif
        </div>
    </div>
    @endif
</div>

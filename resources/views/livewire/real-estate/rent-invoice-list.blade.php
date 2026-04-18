<div>
    @if (session()->has('message'))
        <div class="mb-6 px-4 py-3 rounded-xl bg-green-50 border border-green-200 text-green-700 text-sm">{{ session('message') }}</div>
    @endif
    @if (session()->has('error'))
        <div class="mb-6 px-4 py-3 rounded-xl bg-red-50 border border-red-200 text-red-700 text-sm">{{ session('error') }}</div>
    @endif

    <div class="flex items-center justify-between mb-8">
        <div>
            <h2 class="text-3xl font-extrabold text-slate-900 font-headline">Rent Invoices</h2>
            <p class="text-slate-500 text-sm mt-1">Track and manage rent invoice payments</p>
        </div>
    </div>

    <div class="glass-card rounded-[1.5rem] p-4 mb-6 flex flex-wrap gap-3 items-center">
        <select wire:model.live="statusFilter" class="px-4 py-2 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-primary/20 focus:outline-none">
            <option value="">All Statuses</option>
            <option value="pending">Pending</option>
            <option value="invoiced">Invoiced</option>
            <option value="paid">Paid</option>
            <option value="overdue">Overdue</option>
            <option value="cancelled">Cancelled</option>
        </select>
    </div>

    <div class="glass-card rounded-[1.5rem] overflow-hidden">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-slate-100 text-left">
                    <th class="px-5 py-4 font-bold text-slate-600 text-xs uppercase tracking-widest">Reference</th>
                    <th class="px-5 py-4 font-bold text-slate-600 text-xs uppercase tracking-widest">Property</th>
                    <th class="px-5 py-4 font-bold text-slate-600 text-xs uppercase tracking-widest">Tenant</th>
                    <th class="px-5 py-4 font-bold text-slate-600 text-xs uppercase tracking-widest">Billing Period</th>
                    <th class="px-5 py-4 font-bold text-slate-600 text-xs uppercase tracking-widest">Due Date</th>
                    <th class="px-5 py-4 font-bold text-slate-600 text-xs uppercase tracking-widest">Rent</th>
                    <th class="px-5 py-4 font-bold text-slate-600 text-xs uppercase tracking-widest">Penalty</th>
                    <th class="px-5 py-4 font-bold text-slate-600 text-xs uppercase tracking-widest">Total</th>
                    <th class="px-5 py-4 font-bold text-slate-600 text-xs uppercase tracking-widest">Status</th>
                    <th class="px-5 py-4 font-bold text-slate-600 text-xs uppercase tracking-widest">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-50">
                @forelse($invoices as $invoice)
                @php
                    $statusColors = [
                        'pending'   => 'bg-slate-100 text-slate-500',
                        'invoiced'  => 'bg-blue-100 text-blue-700',
                        'paid'      => 'bg-green-100 text-green-700',
                        'overdue'   => 'bg-red-100 text-red-700',
                        'cancelled' => 'bg-slate-100 text-slate-500',
                    ];
                @endphp
                <tr class="hover:bg-slate-50/50 transition-colors">
                    <td class="px-5 py-4 text-xs text-slate-400 font-mono">{{ $invoice->reference }}</td>
                    <td class="px-5 py-4 font-semibold text-slate-800">{{ $invoice->lease?->property?->name ?? '—' }}</td>
                    <td class="px-5 py-4 text-slate-600">{{ $invoice->lease?->reTenant?->name ?? '—' }}</td>
                    <td class="px-5 py-4 text-slate-600">{{ $invoice->billing_period->format('M Y') }}</td>
                    <td class="px-5 py-4 text-slate-600">{{ $invoice->due_date->format('M d, Y') }}</td>
                    <td class="px-5 py-4 text-slate-600">R {{ number_format($invoice->rent_amount, 2) }}</td>
                    <td class="px-5 py-4 text-slate-600">
                        @if($invoice->penalty_amount > 0)
                            <span class="text-red-600">R {{ number_format($invoice->penalty_amount, 2) }}</span>
                        @else
                            <span class="text-slate-400">—</span>
                        @endif
                    </td>
                    <td class="px-5 py-4 font-bold text-slate-800">R {{ number_format($invoice->total_amount, 2) }}</td>
                    <td class="px-5 py-4">
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-widest {{ $statusColors[$invoice->status] ?? 'bg-slate-100 text-slate-500' }}">
                            {{ $invoice->status }}
                        </span>
                    </td>
                    <td class="px-5 py-4">
                        @can('real_estate.manage_invoices')
                        @if(in_array($invoice->status, ['invoiced', 'overdue']))
                        <button wire:click="markPaid({{ $invoice->id }})" title="Mark Paid" class="text-slate-400 hover:text-green-600 transition-colors">
                            <span class="material-symbols-outlined text-sm">payments</span>
                        </button>
                        @endif
                        @endcan
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="10" class="py-20 text-center text-slate-400">
                        <span class="material-symbols-outlined text-5xl block mb-3">receipt_long</span>
                        <p>No invoices found.</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $invoices->links() }}</div>
</div>

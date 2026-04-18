<div class="space-y-6">
    @php
        $statusColors = [
            'available'         => 'bg-green-100 text-green-700',
            'partially_let'     => 'bg-blue-100 text-blue-700',
            'fully_let'         => 'bg-indigo-100 text-indigo-700',
            'under_maintenance' => 'bg-amber-100 text-amber-700',
            'inactive'          => 'bg-slate-100 text-slate-500',
        ];
        $leaseStatusColors = [
            'draft'      => 'bg-slate-100 text-slate-500',
            'active'     => 'bg-green-100 text-green-700',
            'expired'    => 'bg-orange-100 text-orange-700',
            'terminated' => 'bg-red-100 text-red-700',
            'renewed'    => 'bg-blue-100 text-blue-700',
        ];
        $priorityColors = [
            'low'    => 'bg-slate-100 text-slate-500',
            'medium' => 'bg-blue-100 text-blue-700',
            'high'   => 'bg-amber-100 text-amber-700',
            'urgent' => 'bg-red-100 text-red-700',
        ];
        $mStatusColors = [
            'open'          => 'bg-blue-100 text-blue-700',
            'in_progress'   => 'bg-amber-100 text-amber-700',
            'pending_parts' => 'bg-orange-100 text-orange-700',
            'resolved'      => 'bg-green-100 text-green-700',
            'closed'        => 'bg-slate-100 text-slate-500',
            'cancelled'     => 'bg-slate-100 text-slate-500',
        ];
    @endphp

    {{-- Property Info Card --}}
    <div class="glass-card rounded-[1.5rem] p-6">
        <div class="flex items-start justify-between mb-4">
            <div>
                <h2 class="text-2xl font-extrabold text-slate-900 font-headline">{{ $property->name }}</h2>
                <p class="text-xs text-slate-400 font-mono mt-0.5">{{ $property->reference }}</p>
            </div>
            <span class="px-3 py-1 rounded-full text-xs font-bold uppercase tracking-widest {{ $statusColors[$property->status] ?? 'bg-slate-100 text-slate-500' }}">
                {{ str_replace('_', ' ', $property->status) }}
            </span>
        </div>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
            <div>
                <p class="text-slate-400 text-xs uppercase tracking-widest mb-0.5">Type</p>
                <p class="font-semibold text-slate-700 capitalize">{{ $property->type }}</p>
            </div>
            <div>
                <p class="text-slate-400 text-xs uppercase tracking-widest mb-0.5">City</p>
                <p class="font-semibold text-slate-700">{{ $property->city }}, {{ $property->country }}</p>
            </div>
            <div>
                <p class="text-slate-400 text-xs uppercase tracking-widest mb-0.5">Units</p>
                <p class="font-semibold text-slate-700">{{ $property->units }}</p>
            </div>
            @if($property->market_value)
            <div>
                <p class="text-slate-400 text-xs uppercase tracking-widest mb-0.5">Market Value</p>
                <p class="font-semibold text-slate-700">R {{ number_format($property->market_value, 2) }}</p>
            </div>
            @endif
        </div>
        @if($property->address)
        <p class="text-sm text-slate-500 mt-3">{{ $property->address }}</p>
        @endif
    </div>

    {{-- Active Leases --}}
    <div class="glass-card rounded-[1.5rem] overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-100">
            <h3 class="font-bold text-slate-800">Active Leases</h3>
        </div>
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-slate-100 text-left">
                    <th class="px-5 py-3 font-bold text-slate-600 text-xs uppercase tracking-widest">Tenant</th>
                    <th class="px-5 py-3 font-bold text-slate-600 text-xs uppercase tracking-widest">Start Date</th>
                    <th class="px-5 py-3 font-bold text-slate-600 text-xs uppercase tracking-widest">End Date</th>
                    <th class="px-5 py-3 font-bold text-slate-600 text-xs uppercase tracking-widest">Monthly Rent</th>
                    <th class="px-5 py-3 font-bold text-slate-600 text-xs uppercase tracking-widest">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-50">
                @forelse($property->leases as $lease)
                <tr class="hover:bg-slate-50/50 transition-colors">
                    <td class="px-5 py-3 font-semibold text-slate-800">{{ $lease->reTenant?->name ?? '—' }}</td>
                    <td class="px-5 py-3 text-slate-600">{{ $lease->start_date->format('M d, Y') }}</td>
                    <td class="px-5 py-3 text-slate-600">{{ $lease->end_date->format('M d, Y') }}</td>
                    <td class="px-5 py-3 text-slate-600">R {{ number_format($lease->monthly_rent, 2) }}</td>
                    <td class="px-5 py-3">
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-widest {{ $leaseStatusColors[$lease->status] ?? 'bg-slate-100 text-slate-500' }}">
                            {{ $lease->status }}
                        </span>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="py-10 text-center text-slate-400 text-sm">No active leases.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Open Maintenance Requests --}}
    <div class="glass-card rounded-[1.5rem] overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-100">
            <h3 class="font-bold text-slate-800">Open Maintenance Requests</h3>
        </div>
        <div class="divide-y divide-slate-50">
            @forelse($property->maintenanceRequests as $req)
            <div class="px-6 py-4 flex items-center justify-between hover:bg-slate-50/50 transition-colors">
                <div>
                    <p class="font-semibold text-slate-800 text-sm">{{ $req->title }}</p>
                    <p class="text-xs text-slate-400 mt-0.5">{{ $req->created_at->format('M d, Y') }}</p>
                </div>
                <div class="flex items-center gap-2">
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-widest {{ $priorityColors[$req->priority] ?? 'bg-slate-100 text-slate-500' }}">
                        {{ $req->priority }}
                    </span>
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-widest {{ $mStatusColors[$req->status] ?? 'bg-slate-100 text-slate-500' }}">
                        {{ str_replace('_', ' ', $req->status) }}
                    </span>
                </div>
            </div>
            @empty
            <div class="py-10 text-center text-slate-400 text-sm">No open maintenance requests.</div>
            @endforelse
        </div>
    </div>
</div>

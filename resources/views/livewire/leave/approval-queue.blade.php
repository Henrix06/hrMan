<div>
    @if (session()->has('message'))
        <div class="mb-6 px-4 py-3 rounded-xl bg-green-50 border border-green-200 text-green-700 text-sm flex items-center gap-2">
            <span class="material-symbols-outlined text-sm">check_circle</span>
            {{ session('message') }}
        </div>
    @endif
    @if (session()->has('error'))
        <div class="mb-6 px-4 py-3 rounded-xl bg-red-50 border border-red-200 text-red-700 text-sm flex items-center gap-2">
            <span class="material-symbols-outlined text-sm">error</span>
            {{ session('error') }}
        </div>
    @endif

    <div class="mb-6">
        <h2 class="text-2xl md:text-3xl font-extrabold text-slate-900 font-headline">Leave Approvals</h2>
        <p class="text-slate-500 text-sm mt-1">Review and manage leave requests</p>
    </div>

    {{-- Status Filter --}}
    <div class="glass-card rounded-[1.5rem] p-4 mb-6">
        <div class="flex flex-wrap gap-2">
            <button wire:click="$set('statusFilter', 'pending')"
                class="px-4 py-2 rounded-xl text-sm font-bold transition-colors {{ $statusFilter === 'pending' ? 'bg-amber-500 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
                Pending
            </button>
            <button wire:click="$set('statusFilter', 'approved')"
                class="px-4 py-2 rounded-xl text-sm font-bold transition-colors {{ $statusFilter === 'approved' ? 'bg-green-500 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
                Approved
            </button>
            <button wire:click="$set('statusFilter', 'rejected')"
                class="px-4 py-2 rounded-xl text-sm font-bold transition-colors {{ $statusFilter === 'rejected' ? 'bg-red-500 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
                Rejected
            </button>
            <button wire:click="$set('statusFilter', '')"
                class="px-4 py-2 rounded-xl text-sm font-bold transition-colors {{ $statusFilter === '' ? 'bg-primary text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
                All
            </button>
        </div>
    </div>

    {{-- Desktop Table --}}
    <div class="hidden md:block glass-card rounded-[2rem] overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-slate-50/80">
                    <tr>
                        <th class="text-left px-6 py-4 font-semibold text-slate-500 uppercase tracking-wider text-xs">Employee</th>
                        <th class="text-left px-6 py-4 font-semibold text-slate-500 uppercase tracking-wider text-xs">Leave Type</th>
                        <th class="text-left px-6 py-4 font-semibold text-slate-500 uppercase tracking-wider text-xs">Dates</th>
                        <th class="text-left px-6 py-4 font-semibold text-slate-500 uppercase tracking-wider text-xs">Days</th>
                        <th class="text-left px-6 py-4 font-semibold text-slate-500 uppercase tracking-wider text-xs">Status</th>
                        <th class="px-6 py-4"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($leaveRequests as $request)
                    <tr class="hover:bg-slate-50/50 transition-colors">
                        <td class="px-6 py-4">
                            <p class="font-bold text-slate-800">{{ $request->user->name }}</p>
                            <p class="text-xs text-slate-400">{{ $request->user->email }}</p>
                        </td>
                        <td class="px-6 py-4 text-slate-600">{{ $request->leaveType->name }}</td>
                        <td class="px-6 py-4 text-slate-600">
                            {{ $request->start_date->format('M d') }} - {{ $request->end_date->format('M d, Y') }}
                        </td>
                        <td class="px-6 py-4">
                            <span class="bg-primary/10 text-primary font-bold px-2 py-0.5 rounded-full text-xs">{{ $request->total_days }}</span>
                        </td>
                        <td class="px-6 py-4">
                            <span class="px-2 py-0.5 rounded-full text-xs font-bold
                                @if($request->status === 'approved') bg-green-100 text-green-700
                                @elseif($request->status === 'rejected') bg-red-100 text-red-700
                                @elseif($request->status === 'pending') bg-amber-100 text-amber-700
                                @else bg-slate-100 text-slate-600 @endif">
                                {{ ucfirst($request->status) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-right">
                            @if($request->status === 'pending')
                            <div class="flex items-center justify-end gap-2">
                                <button wire:click="approveLeave({{ $request->id }})"
                                    wire:confirm="Approve this leave request?"
                                    class="p-2 rounded-xl text-slate-400 hover:text-green-600 hover:bg-green-50 transition-colors" title="Approve">
                                    <span class="material-symbols-outlined text-sm">check_circle</span>
                                </button>
                                <button wire:click="rejectLeave({{ $request->id }})"
                                    wire:confirm="Reject this leave request?"
                                    class="p-2 rounded-xl text-slate-400 hover:text-red-600 hover:bg-red-50 transition-colors" title="Reject">
                                    <span class="material-symbols-outlined text-sm">cancel</span>
                                </button>
                            </div>
                            @else
                            <span class="text-xs text-slate-400">
                                {{ $request->approvedBy?->name ?? '-' }}
                            </span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-6 py-16 text-center text-slate-400">
                            <span class="material-symbols-outlined text-5xl block mb-3">how_to_reg</span>
                            <p>No leave requests found.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-4 border-t border-slate-100">{{ $leaveRequests->links() }}</div>
    </div>

    {{-- Mobile Cards --}}
    <div class="md:hidden space-y-3">
        @forelse($leaveRequests as $request)
        <div class="glass-card rounded-[1.5rem] p-4">
            <div class="flex items-start justify-between">
                <div>
                    <p class="font-bold text-slate-800">{{ $request->user->name }}</p>
                    <p class="text-xs text-slate-400">{{ $request->leaveType->name }}</p>
                </div>
                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold
                    @if($request->status === 'approved') bg-green-100 text-green-700
                    @elseif($request->status === 'rejected') bg-red-100 text-red-700
                    @elseif($request->status === 'pending') bg-amber-100 text-amber-700
                    @else bg-slate-100 text-slate-600 @endif">
                    {{ ucfirst($request->status) }}
                </span>
            </div>
            <div class="mt-3 grid grid-cols-2 gap-2 text-xs">
                <div class="bg-slate-50 rounded-lg p-2">
                    <p class="text-slate-400">Dates</p>
                    <p class="font-semibold text-slate-700">{{ $request->start_date->format('M d') }} - {{ $request->end_date->format('M d') }}</p>
                </div>
                <div class="bg-slate-50 rounded-lg p-2">
                    <p class="text-slate-400">Days</p>
                    <p class="font-semibold text-primary">{{ $request->total_days }}</p>
                </div>
            </div>
            @if($request->status === 'pending')
            <div class="mt-3 flex items-center gap-2">
                <button wire:click="approveLeave({{ $request->id }})"
                    wire:confirm="Approve this leave request?"
                    class="flex-1 text-center text-xs font-bold py-2 rounded-xl bg-green-100 text-green-700 hover:bg-green-200 transition-colors">
                    Approve
                </button>
                <button wire:click="rejectLeave({{ $request->id }})"
                    wire:confirm="Reject this leave request?"
                    class="flex-1 text-center text-xs font-bold py-2 rounded-xl bg-red-100 text-red-700 hover:bg-red-200 transition-colors">
                    Reject
                </button>
            </div>
            @else
            <p class="mt-2 text-xs text-slate-400">Reviewed by: {{ $request->approvedBy?->name ?? '-' }}</p>
            @endif
        </div>
        @empty
        <div class="glass-card rounded-[2rem] py-16 text-center text-slate-400">
            <span class="material-symbols-outlined text-5xl block mb-3">how_to_reg</span>
            <p>No leave requests found.</p>
        </div>
        @endforelse
    </div>
</div>

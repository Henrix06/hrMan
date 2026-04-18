<div>
    @if (session()->has('message'))
        <div class="mb-6 px-4 py-3 rounded-xl bg-green-50 border border-green-200 text-green-700 text-sm">{{ session('message') }}</div>
    @endif
    @if (session()->has('error'))
        <div class="mb-6 px-4 py-3 rounded-xl bg-red-50 border border-red-200 text-red-700 text-sm">{{ session('error') }}</div>
    @endif

    <div class="flex items-center justify-between mb-8">
        <div>
            <h2 class="text-3xl font-extrabold text-slate-900 font-headline">Maintenance Requests</h2>
            <p class="text-slate-500 text-sm mt-1">Track and manage property maintenance</p>
        </div>
    </div>

    <div class="glass-card rounded-[1.5rem] p-4 mb-6 flex flex-wrap gap-3 items-center">
        <select wire:model.live="statusFilter" class="px-4 py-2 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-primary/20 focus:outline-none">
            <option value="">All Statuses</option>
            <option value="open">Open</option>
            <option value="in_progress">In Progress</option>
            <option value="pending_parts">Pending Parts</option>
            <option value="resolved">Resolved</option>
            <option value="closed">Closed</option>
            <option value="cancelled">Cancelled</option>
        </select>
        <select wire:model.live="priorityFilter" class="px-4 py-2 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-primary/20 focus:outline-none">
            <option value="">All Priorities</option>
            <option value="low">Low</option>
            <option value="medium">Medium</option>
            <option value="high">High</option>
            <option value="urgent">Urgent</option>
        </select>
    </div>

    <div class="glass-card rounded-[1.5rem] overflow-hidden">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-slate-100 text-left">
                    <th class="px-5 py-4 font-bold text-slate-600 text-xs uppercase tracking-widest">Reference</th>
                    <th class="px-5 py-4 font-bold text-slate-600 text-xs uppercase tracking-widest">Property</th>
                    <th class="px-5 py-4 font-bold text-slate-600 text-xs uppercase tracking-widest">Title</th>
                    <th class="px-5 py-4 font-bold text-slate-600 text-xs uppercase tracking-widest">Category</th>
                    <th class="px-5 py-4 font-bold text-slate-600 text-xs uppercase tracking-widest">Priority</th>
                    <th class="px-5 py-4 font-bold text-slate-600 text-xs uppercase tracking-widest">Status</th>
                    <th class="px-5 py-4 font-bold text-slate-600 text-xs uppercase tracking-widest">Assigned To</th>
                    <th class="px-5 py-4 font-bold text-slate-600 text-xs uppercase tracking-widest">Created</th>
                    <th class="px-5 py-4 font-bold text-slate-600 text-xs uppercase tracking-widest">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-50">
                @forelse($requests as $request)
                @php
                    $priorityColors = [
                        'low'    => 'bg-slate-100 text-slate-500',
                        'medium' => 'bg-blue-100 text-blue-700',
                        'high'   => 'bg-amber-100 text-amber-700',
                        'urgent' => 'bg-red-100 text-red-700',
                    ];
                    $statusColors = [
                        'open'          => 'bg-blue-100 text-blue-700',
                        'in_progress'   => 'bg-amber-100 text-amber-700',
                        'pending_parts' => 'bg-orange-100 text-orange-700',
                        'resolved'      => 'bg-green-100 text-green-700',
                        'closed'        => 'bg-slate-100 text-slate-500',
                        'cancelled'     => 'bg-slate-100 text-slate-500',
                    ];
                @endphp
                <tr class="hover:bg-slate-50/50 transition-colors">
                    <td class="px-5 py-4 text-xs text-slate-400 font-mono">{{ $request->reference }}</td>
                    <td class="px-5 py-4 font-semibold text-slate-800">{{ $request->property?->name ?? '—' }}</td>
                    <td class="px-5 py-4 text-slate-700">{{ $request->title }}</td>
                    <td class="px-5 py-4 text-slate-600 capitalize">{{ $request->category }}</td>
                    <td class="px-5 py-4">
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-widest {{ $priorityColors[$request->priority] ?? 'bg-slate-100 text-slate-500' }}">
                            {{ $request->priority }}
                        </span>
                    </td>
                    <td class="px-5 py-4">
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-widest {{ $statusColors[$request->status] ?? 'bg-slate-100 text-slate-500' }}">
                            {{ str_replace('_', ' ', $request->status) }}
                        </span>
                    </td>
                    <td class="px-5 py-4 text-slate-600">{{ $request->assignee?->name ?? '—' }}</td>
                    <td class="px-5 py-4 text-slate-400 text-xs">{{ $request->created_at->format('M d, Y') }}</td>
                    <td class="px-5 py-4">
                        <div class="flex items-center gap-2">
                            @can('real_estate.manage_maintenance')
                            @if($request->status === 'open')
                            <button wire:click="openAssign({{ $request->id }})" title="Assign" class="text-slate-400 hover:text-primary transition-colors">
                                <span class="material-symbols-outlined text-sm">person_add</span>
                            </button>
                            @endif
                            @if($request->status === 'in_progress')
                            <button wire:click="openResolve({{ $request->id }})" title="Resolve" class="text-slate-400 hover:text-green-600 transition-colors">
                                <span class="material-symbols-outlined text-sm">check_circle</span>
                            </button>
                            @endif
                            @if($request->status === 'resolved')
                            <button wire:click="closeRequest({{ $request->id }})" wire:confirm="Close this maintenance request?" title="Close" class="text-slate-400 hover:text-slate-700 transition-colors">
                                <span class="material-symbols-outlined text-sm">lock</span>
                            </button>
                            @endif
                            @if(!in_array($request->status, ['closed', 'resolved', 'cancelled']))
                            <button wire:click="cancelRequest({{ $request->id }})" wire:confirm="Cancel this maintenance request?" title="Cancel" class="text-slate-400 hover:text-red-500 transition-colors">
                                <span class="material-symbols-outlined text-sm">cancel</span>
                            </button>
                            @endif
                            @endcan
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="9" class="py-20 text-center text-slate-400">
                        <span class="material-symbols-outlined text-5xl block mb-3">build</span>
                        <p>No maintenance requests found.</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $requests->links() }}</div>

    {{-- Assign Modal --}}
    @if($showAssignModal)
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm">
        <div class="bg-white rounded-[2rem] shadow-2xl w-full max-w-md mx-4 p-8">
            <h3 class="text-2xl font-bold font-headline text-slate-900 mb-6">Assign Request</h3>
            <form wire:submit="confirmAssign" class="space-y-4">
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1">Assign To *</label>
                    <select wire:model="assigneeId" class="w-full px-4 py-2 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-primary/20 focus:outline-none">
                        <option value="">— Select User —</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}">{{ $user->name }}</option>
                        @endforeach
                    </select>
                    @error('assigneeId') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div class="flex gap-3 pt-2">
                    <button type="submit" class="flex-1 bg-primary text-on-primary py-2.5 rounded-xl font-bold font-headline hover:opacity-90 transition-opacity">Assign</button>
                    <button type="button" wire:click="$set('showAssignModal', false)" class="flex-1 bg-slate-100 text-slate-700 py-2.5 rounded-xl font-bold font-headline hover:bg-slate-200 transition-colors">Cancel</button>
                </div>
            </form>
        </div>
    </div>
    @endif

    {{-- Resolve Modal --}}
    @if($showResolveModal)
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm">
        <div class="bg-white rounded-[2rem] shadow-2xl w-full max-w-md mx-4 p-8">
            <h3 class="text-2xl font-bold font-headline text-slate-900 mb-6">Resolve Request</h3>
            <form wire:submit="confirmResolve" class="space-y-4">
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1">Resolution Notes *</label>
                    <textarea wire:model="resolutionNotes" rows="4" class="w-full px-4 py-2 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-primary/20 focus:outline-none"></textarea>
                    @error('resolutionNotes') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1">Repair Cost</label>
                    <input wire:model="repairCost" type="number" step="0.01" min="0" class="w-full px-4 py-2 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-primary/20 focus:outline-none"/>
                    @error('repairCost') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div class="flex gap-3 pt-2">
                    <button type="submit" class="flex-1 bg-green-600 text-white py-2.5 rounded-xl font-bold font-headline hover:opacity-90 transition-opacity">Mark Resolved</button>
                    <button type="button" wire:click="$set('showResolveModal', false)" class="flex-1 bg-slate-100 text-slate-700 py-2.5 rounded-xl font-bold font-headline hover:bg-slate-200 transition-colors">Cancel</button>
                </div>
            </form>
        </div>
    </div>
    @endif
</div>

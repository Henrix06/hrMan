<div>
    @if (session()->has('message'))
        <div class="mb-6 px-4 py-3 rounded-xl bg-green-50 border border-green-200 text-green-700 text-sm">{{ session('message') }}</div>
    @endif
    @if (session()->has('error'))
        <div class="mb-6 px-4 py-3 rounded-xl bg-red-50 border border-red-200 text-red-700 text-sm">{{ session('error') }}</div>
    @endif

    <div class="flex items-center justify-between mb-8">
        <div>
            <h2 class="text-3xl font-extrabold text-slate-900 font-headline">Agent Assignments</h2>
            <p class="text-slate-500 text-sm mt-1">Assign agents and managers to units</p>
        </div>
        @can('real_estate.manage_leases')
        <button wire:click="openCreate" class="bg-gradient-to-br from-primary to-primary-container text-on-primary px-5 py-2.5 rounded-xl font-bold font-headline shadow-lg shadow-primary/20 hover:opacity-90 transition-opacity flex items-center gap-2">
            <span class="material-symbols-outlined text-sm">add</span> New Assignment
        </button>
        @endcan
    </div>

    <div class="glass-card rounded-[1.5rem] p-4 mb-6 flex flex-wrap gap-3 items-center">
        <input wire:model.live.debounce.300ms="search" type="text" placeholder="Search by reference, agent, unit..."
            class="px-4 py-2 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-primary/20 focus:outline-none flex-1 min-w-48"/>
        <select wire:model.live="statusFilter" class="px-4 py-2 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-primary/20 focus:outline-none">
            <option value="">All Statuses</option>
            <option value="active">Active</option>
            <option value="inactive">Inactive</option>
        </select>
    </div>

    <div class="glass-card rounded-[1.5rem] overflow-hidden">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-slate-100 text-left">
                    <th class="px-5 py-4 font-bold text-slate-600 text-xs uppercase tracking-widest">Reference</th>
                    <th class="px-5 py-4 font-bold text-slate-600 text-xs uppercase tracking-widest">Unit</th>
                    <th class="px-5 py-4 font-bold text-slate-600 text-xs uppercase tracking-widest">Agent</th>
                    <th class="px-5 py-4 font-bold text-slate-600 text-xs uppercase tracking-widest">Role</th>
                    <th class="px-5 py-4 font-bold text-slate-600 text-xs uppercase tracking-widest">Start Date</th>
                    <th class="px-5 py-4 font-bold text-slate-600 text-xs uppercase tracking-widest">Status</th>
                    <th class="px-5 py-4 font-bold text-slate-600 text-xs uppercase tracking-widest">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-50">
                @forelse($assignments as $assignment)
                @php
                    $statusColors = [
                        'active'   => 'bg-green-100 text-green-700',
                        'inactive' => 'bg-slate-100 text-slate-500',
                    ];
                    $roleColors = [
                        'agent'   => 'bg-blue-100 text-blue-700',
                        'manager' => 'bg-primary/10 text-primary',
                    ];
                @endphp
                <tr class="hover:bg-slate-50/50 transition-colors">
                    <td class="px-5 py-4 text-xs text-slate-400 font-mono">{{ $assignment->reference }}</td>
                    <td class="px-5 py-4 font-bold text-slate-800">
                        {{ $assignment->unit->property->name ?? 'N/A' }} - Unit {{ $assignment->unit->unit_number ?? 'N/A' }}
                    </td>
                    <td class="px-5 py-4 text-slate-600">{{ $assignment->agent->name ?? 'N/A' }}</td>
                    <td class="px-5 py-4">
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-widest {{ $roleColors[$assignment->agent_role] ?? 'bg-slate-100 text-slate-500' }}">
                            {{ $assignment->agent_role }}
                        </span>
                    </td>
                    <td class="px-5 py-4 text-slate-600">{{ $assignment->start_date->toFormattedDateString() }}</td>
                    <td class="px-5 py-4">
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-widest {{ $statusColors[$assignment->status] ?? 'bg-slate-100 text-slate-500' }}">
                            {{ $assignment->status }}
                        </span>
                    </td>
                    <td class="px-5 py-4">
                        @can('real_estate.manage_leases')
                        <button wire:click="openEdit({{ $assignment->id }})" title="Edit" class="text-slate-400 hover:text-primary transition-colors">
                            <span class="material-symbols-outlined text-sm">edit</span>
                        </button>
                        @endcan
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="py-20 text-center text-slate-400">
                        <span class="material-symbols-outlined text-5xl block mb-3">person_add</span>
                        <p>No assignments found.</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $assignments->links() }}</div>

    @if($showModal)
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm">
        <div class="bg-white rounded-[2rem] shadow-2xl w-full max-w-lg mx-4 p-8 max-h-[90vh] overflow-y-auto">
            <h3 class="text-2xl font-bold font-headline text-slate-900 mb-6">{{ $editingId ? 'Edit Assignment' : 'New Assignment' }}</h3>
            <form wire:submit="save" class="space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div class="col-span-2">
                        <label class="block text-sm font-semibold text-slate-700 mb-1">Unit *</label>
                        <select wire:model="unitId" class="w-full px-4 py-2 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-primary/20 focus:outline-none">
                            <option value="">— Select —</option>
                            @foreach($units as $id => $unitNumber)
                            <option value="{{ $id }}">Unit {{ $unitNumber }}</option>
                            @endforeach
                        </select>
                        @error('unitId') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1">Agent *</label>
                        <select wire:model="agentId" class="w-full px-4 py-2 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-primary/20 focus:outline-none">
                            <option value="">— Select —</option>
                            @foreach($agents as $id => $name)
                            <option value="{{ $id }}">{{ $name }}</option>
                            @endforeach
                        </select>
                        @error('agentId') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1">Role *</label>
                        <select wire:model="agentRole" class="w-full px-4 py-2 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-primary/20 focus:outline-none">
                            <option value="agent">Agent</option>
                            <option value="manager">Manager</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1">Start Date *</label>
                        <input wire:model="startDate" type="date" class="w-full px-4 py-2 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-primary/20 focus:outline-none"/>
                        @error('startDate') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1">End Date</label>
                        <input wire:model="endDate" type="date" class="w-full px-4 py-2 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-primary/20 focus:outline-none"/>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1">Status *</label>
                        <select wire:model="status" class="w-full px-4 py-2 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-primary/20 focus:outline-none">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                    <div class="col-span-2">
                        <label class="block text-sm font-semibold text-slate-700 mb-1">Notes</label>
                        <textarea wire:model="notes" rows="3" class="w-full px-4 py-2 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-primary/20 focus:outline-none"></textarea>
                    </div>
                </div>
                <div class="flex gap-3 pt-2">
                    <button type="submit" class="flex-1 bg-primary text-on-primary py-2.5 rounded-xl font-bold font-headline hover:opacity-90 transition-opacity">
                        {{ $editingId ? 'Update' : 'Create' }}
                    </button>
                    <button type="button" wire:click="$set('showModal', false)" class="flex-1 bg-slate-100 text-slate-700 py-2.5 rounded-xl font-bold font-headline hover:bg-slate-200 transition-colors">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>
    @endif
</div>

<div>
    @if (session()->has('message'))
        <div class="mb-6 px-4 py-3 rounded-xl bg-green-50 border border-green-200 text-green-700 text-sm">{{ session('message') }}</div>
    @endif

    <div class="flex items-center justify-between mb-8">
        <div>
            <h2 class="text-3xl font-extrabold text-slate-900 font-headline">Departments</h2>
            <p class="text-slate-500 text-sm mt-1">Manage organisational structure</p>
        </div>
        @can('manage settings')
        <button wire:click="openCreate" class="bg-gradient-to-br from-primary to-primary-container text-on-primary px-5 py-2.5 rounded-xl font-bold font-headline shadow-lg shadow-primary/20 hover:opacity-90 transition-opacity flex items-center gap-2">
            <span class="material-symbols-outlined text-sm">add</span> New Department
        </button>
        @endcan
    </div>

    <div class="glass-card rounded-[2rem] overflow-hidden">
        <div class="p-6 border-b border-slate-100">
            <input wire:model.live.debounce.300ms="search" type="text" placeholder="Search departments..."
                class="w-full max-w-sm px-4 py-2 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-primary/20 focus:outline-none"/>
        </div>

        <table class="w-full text-sm">
            <thead class="bg-slate-50/80">
                <tr>
                    <th class="text-left px-6 py-4 font-semibold text-slate-500 uppercase tracking-wider text-xs">Department</th>
                    <th class="text-left px-6 py-4 font-semibold text-slate-500 uppercase tracking-wider text-xs">Code</th>
                    <th class="text-left px-6 py-4 font-semibold text-slate-500 uppercase tracking-wider text-xs">Manager</th>
                    <th class="text-left px-6 py-4 font-semibold text-slate-500 uppercase tracking-wider text-xs">Employees</th>
                    <th class="text-left px-6 py-4 font-semibold text-slate-500 uppercase tracking-wider text-xs">Status</th>
                    @can('manage settings')
                    <th class="px-6 py-4"></th>
                    @endcan
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($departments as $dept)
                <tr class="hover:bg-slate-50/50 transition-colors">
                    <td class="px-6 py-4">
                        <p class="font-bold text-slate-800">{{ $dept->name }}</p>
                        @if($dept->description)
                        <p class="text-xs text-slate-400 mt-0.5 truncate max-w-xs">{{ $dept->description }}</p>
                        @endif
                    </td>
                    <td class="px-6 py-4 text-slate-500">{{ $dept->code ?? '—' }}</td>
                    <td class="px-6 py-4 text-slate-700">{{ $dept->manager?->name ?? '—' }}</td>
                    <td class="px-6 py-4">
                        <span class="bg-primary/10 text-primary font-bold px-2 py-0.5 rounded-full text-xs">{{ $dept->employees_count }}</span>
                    </td>
                    <td class="px-6 py-4">
                        <span class="px-2 py-0.5 rounded-full text-xs font-bold {{ $dept->active ? 'bg-green-100 text-green-700' : 'bg-slate-100 text-slate-500' }}">
                            {{ $dept->active ? 'Active' : 'Inactive' }}
                        </span>
                    </td>
                    @can('manage settings')
                    <td class="px-6 py-4 text-right">
                        <button wire:click="openEdit({{ $dept->id }})" class="text-slate-400 hover:text-primary transition-colors mr-3">
                            <span class="material-symbols-outlined text-sm">edit</span>
                        </button>
                        <button wire:click="delete({{ $dept->id }})" wire:confirm="Delete this department?" class="text-slate-400 hover:text-error transition-colors">
                            <span class="material-symbols-outlined text-sm">delete</span>
                        </button>
                    </td>
                    @endcan
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="px-6 py-16 text-center text-slate-400">
                        <span class="material-symbols-outlined text-4xl block mb-2">corporate_fare</span>
                        No departments found.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>

        <div class="p-4 border-t border-slate-100">{{ $departments->links() }}</div>
    </div>

    <!-- Modal -->
    @if($showModal)
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm">
        <div class="bg-white rounded-[2rem] shadow-2xl w-full max-w-lg mx-4 p-8">
            <h3 class="text-2xl font-bold font-headline text-slate-900 mb-6">{{ $editing ? 'Edit' : 'New' }} Department</h3>
            <form wire:submit="save" class="space-y-4">
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1">Name *</label>
                    <input wire:model="name" type="text" class="w-full px-4 py-2 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-primary/20 focus:outline-none"/>
                    @error('name') <p class="text-error text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1">Code</label>
                    <input wire:model="code" type="text" placeholder="e.g. ENG" class="w-full px-4 py-2 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-primary/20 focus:outline-none"/>
                    @error('code') <p class="text-error text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1">Description</label>
                    <textarea wire:model="description" rows="2" class="w-full px-4 py-2 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-primary/20 focus:outline-none"></textarea>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1">Manager</label>
                    <select wire:model="manager_id" class="w-full px-4 py-2 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-primary/20 focus:outline-none">
                        <option value="">— None —</option>
                        @foreach($managers as $m)
                        <option value="{{ $m->id }}">{{ $m->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="flex items-center gap-2">
                    <input wire:model="active" type="checkbox" id="active" class="rounded"/>
                    <label for="active" class="text-sm font-semibold text-slate-700">Active</label>
                </div>
                <div class="flex gap-3 pt-2">
                    <button type="submit" class="flex-1 bg-primary text-on-primary py-2.5 rounded-xl font-bold font-headline hover:opacity-90 transition-opacity">
                        {{ $editing ? 'Update' : 'Create' }}
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

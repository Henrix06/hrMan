<div>
    @if (session()->has('message'))
        <div class="mb-6 px-4 py-3 rounded-xl bg-green-50 border border-green-200 text-green-700 text-sm">{{ session('message') }}</div>
    @endif
    @if (session()->has('error'))
        <div class="mb-6 px-4 py-3 rounded-xl bg-red-50 border border-red-200 text-red-700 text-sm">{{ session('error') }}</div>
    @endif

    <div class="flex items-center justify-between mb-8">
        <div>
            <h2 class="text-3xl font-extrabold text-slate-900 font-headline">Properties</h2>
            <p class="text-slate-500 text-sm mt-1">Manage your real estate portfolio</p>
        </div>
        @can('real_estate.manage_leases')
        <button wire:click="openCreate" class="bg-gradient-to-br from-primary to-primary-container text-on-primary px-5 py-2.5 rounded-xl font-bold font-headline shadow-lg shadow-primary/20 hover:opacity-90 transition-opacity flex items-center gap-2">
            <span class="material-symbols-outlined text-sm">add</span> New Property
        </button>
        @endcan
    </div>

    <div class="glass-card rounded-[1.5rem] p-4 mb-6 flex flex-wrap gap-3 items-center">
        <input wire:model.live.debounce.300ms="search" type="text" placeholder="Search by name, reference, city..."
            class="px-4 py-2 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-primary/20 focus:outline-none flex-1 min-w-48"/>
        <select wire:model.live="statusFilter" class="px-4 py-2 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-primary/20 focus:outline-none">
            <option value="">All Statuses</option>
            <option value="available">Available</option>
            <option value="partially_let">Partially Let</option>
            <option value="fully_let">Fully Let</option>
            <option value="under_maintenance">Under Maintenance</option>
            <option value="inactive">Inactive</option>
        </select>
        <select wire:model.live="typeFilter" class="px-4 py-2 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-primary/20 focus:outline-none">
            <option value="">All Types</option>
            <option value="residential">Residential</option>
            <option value="commercial">Commercial</option>
            <option value="industrial">Industrial</option>
        </select>
    </div>

    <div class="glass-card rounded-[1.5rem] overflow-hidden">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-slate-100 text-left">
                    <th class="px-5 py-4 font-bold text-slate-600 text-xs uppercase tracking-widest">Reference</th>
                    <th class="px-5 py-4 font-bold text-slate-600 text-xs uppercase tracking-widest">Name</th>
                    <th class="px-5 py-4 font-bold text-slate-600 text-xs uppercase tracking-widest">Type</th>
                    <th class="px-5 py-4 font-bold text-slate-600 text-xs uppercase tracking-widest">City</th>
                    <th class="px-5 py-4 font-bold text-slate-600 text-xs uppercase tracking-widest">Status</th>
                    <th class="px-5 py-4 font-bold text-slate-600 text-xs uppercase tracking-widest">Units / Active</th>
                    <th class="px-5 py-4 font-bold text-slate-600 text-xs uppercase tracking-widest">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-50">
                @forelse($properties as $property)
                @php
                    $statusColors = [
                        'available'         => 'bg-green-100 text-green-700',
                        'partially_let'     => 'bg-blue-100 text-blue-700',
                        'fully_let'         => 'bg-primary/10 text-primary',
                        'under_maintenance' => 'bg-amber-100 text-amber-700',
                        'inactive'          => 'bg-slate-100 text-slate-500',
                    ];
                    $activeLeases = $property->leases->count();
                @endphp
                <tr class="hover:bg-slate-50/50 transition-colors">
                    <td class="px-5 py-4 text-xs text-slate-400 font-mono">{{ $property->reference }}</td>
                    <td class="px-5 py-4 font-bold text-slate-800">{{ $property->name }}</td>
                    <td class="px-5 py-4 text-slate-600 capitalize">{{ $property->type }}</td>
                    <td class="px-5 py-4 text-slate-600">{{ $property->city }}</td>
                    <td class="px-5 py-4">
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-widest {{ $statusColors[$property->status] ?? 'bg-slate-100 text-slate-500' }}">
                            {{ str_replace('_', ' ', $property->status) }}
                        </span>
                    </td>
                    <td class="px-5 py-4 text-slate-600">{{ $property->units }} / {{ $activeLeases }}</td>
                    <td class="px-5 py-4">
                        @can('real_estate.manage_leases')
                        <button wire:click="openEdit({{ $property->id }})" title="Edit" class="text-slate-400 hover:text-primary transition-colors">
                            <span class="material-symbols-outlined text-sm">edit</span>
                        </button>
                        @endcan
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="py-20 text-center text-slate-400">
                        <span class="material-symbols-outlined text-5xl block mb-3">apartment</span>
                        <p>No properties found.</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $properties->links() }}</div>

    @if($showModal)
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm">
        <div class="bg-white rounded-[2rem] shadow-2xl w-full max-w-lg mx-4 p-8 max-h-[90vh] overflow-y-auto">
            <h3 class="text-2xl font-bold font-headline text-slate-900 mb-6">{{ $editingId ? 'Edit Property' : 'New Property' }}</h3>
            <form wire:submit="save" class="space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div class="col-span-2">
                        <label class="block text-sm font-semibold text-slate-700 mb-1">Name *</label>
                        <input wire:model="name" type="text" class="w-full px-4 py-2 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-primary/20 focus:outline-none"/>
                        @error('name') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1">Type *</label>
                        <select wire:model="type" class="w-full px-4 py-2 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-primary/20 focus:outline-none">
                            <option value="">— Select —</option>
                            <option value="residential">Residential</option>
                            <option value="commercial">Commercial</option>
                            <option value="industrial">Industrial</option>
                        </select>
                        @error('type') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1">Status *</label>
                        <select wire:model="status" class="w-full px-4 py-2 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-primary/20 focus:outline-none">
                            <option value="available">Available</option>
                            <option value="partially_let">Partially Let</option>
                            <option value="fully_let">Fully Let</option>
                            <option value="under_maintenance">Under Maintenance</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                    <div class="col-span-2">
                        <label class="block text-sm font-semibold text-slate-700 mb-1">Address *</label>
                        <input wire:model="address" type="text" class="w-full px-4 py-2 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-primary/20 focus:outline-none"/>
                        @error('address') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1">City *</label>
                        <input wire:model="city" type="text" class="w-full px-4 py-2 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-primary/20 focus:outline-none"/>
                        @error('city') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1">Country</label>
                        <input wire:model="country" type="text" class="w-full px-4 py-2 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-primary/20 focus:outline-none"/>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1">Units *</label>
                        <input wire:model="units" type="number" min="1" class="w-full px-4 py-2 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-primary/20 focus:outline-none"/>
                        @error('units') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1">Market Value</label>
                        <input wire:model="market_value" type="number" step="0.01" min="0" class="w-full px-4 py-2 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-primary/20 focus:outline-none"/>
                    </div>
                    <div class="col-span-2">
                        <label class="block text-sm font-semibold text-slate-700 mb-1">Description</label>
                        <textarea wire:model="description" rows="3" class="w-full px-4 py-2 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-primary/20 focus:outline-none"></textarea>
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

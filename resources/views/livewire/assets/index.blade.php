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

    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
        <div>
            <h2 class="text-2xl md:text-3xl font-extrabold text-slate-900 font-headline">Asset Management</h2>
            <p class="text-slate-500 text-sm mt-1">Track, assign, and manage company assets</p>
        </div>
        @can('manage assets')
        <button wire:click="openCreate"
            class="bg-gradient-to-br from-primary to-primary-container text-on-primary px-5 py-2.5 rounded-xl font-bold font-headline shadow-lg shadow-primary/20 hover:opacity-90 transition-opacity flex items-center justify-center gap-2 w-full sm:w-auto">
            <span class="material-symbols-outlined text-sm">add</span> New Asset
        </button>
        @endcan
    </div>

    {{-- Filters --}}
    <div class="glass-card rounded-[1.5rem] p-4 mb-6">
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
            <input wire:model.live.debounce.300ms="search" type="text" placeholder="Search by name, tag, serial..."
                class="px-4 py-2 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-primary/20 focus:outline-none sm:col-span-1"/>
            <select wire:model.live="statusFilter" class="px-4 py-2 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-primary/20 focus:outline-none">
                <option value="">All Statuses</option>
                <option value="available">Available</option>
                <option value="assigned">Assigned</option>
                <option value="under_maintenance">Under Maintenance</option>
                <option value="retired">Retired</option>
            </select>
            <select wire:model.live="categoryFilter" class="px-4 py-2 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-primary/20 focus:outline-none">
                <option value="">All Categories</option>
                @foreach($categories as $cat)
                    <option value="{{ $cat }}">{{ ucfirst($cat) }}</option>
                @endforeach
            </select>
        </div>
    </div>

    {{-- Desktop Table --}}
    <div class="hidden md:block glass-card rounded-[1.5rem] overflow-hidden">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-slate-100 text-left">
                    <th class="px-5 py-4 font-bold text-slate-600 text-xs uppercase tracking-widest">Asset</th>
                    <th class="px-5 py-4 font-bold text-slate-600 text-xs uppercase tracking-widest">Category</th>
                    <th class="px-5 py-4 font-bold text-slate-600 text-xs uppercase tracking-widest">Condition</th>
                    <th class="px-5 py-4 font-bold text-slate-600 text-xs uppercase tracking-widest">Status</th>
                    <th class="px-5 py-4 font-bold text-slate-600 text-xs uppercase tracking-widest">Assigned To</th>
                    <th class="px-5 py-4 font-bold text-slate-600 text-xs uppercase tracking-widest">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-50">
                @forelse($assets as $asset)
                @php
                    $statusColors = [
                        'available'         => 'bg-green-100 text-green-700',
                        'assigned'          => 'bg-blue-100 text-blue-700',
                        'under_maintenance' => 'bg-amber-100 text-amber-700',
                        'retired'           => 'bg-slate-100 text-slate-500',
                    ];
                    $conditionColors = [
                        'new'     => 'bg-emerald-100 text-emerald-700',
                        'good'    => 'bg-green-100 text-green-700',
                        'fair'    => 'bg-amber-100 text-amber-700',
                        'poor'    => 'bg-orange-100 text-orange-700',
                        'damaged' => 'bg-red-100 text-red-700',
                    ];
                @endphp
                <tr class="hover:bg-slate-50/50 transition-colors">
                    <td class="px-5 py-4">
                        <p class="font-bold text-slate-800">{{ $asset->name }}</p>
                        <p class="text-xs text-slate-400 mt-0.5">{{ $asset->asset_tag }}{{ $asset->brand ? ' · ' . $asset->brand : '' }}{{ $asset->model ? ' ' . $asset->model : '' }}</p>
                    </td>
                    <td class="px-5 py-4 text-slate-600 capitalize">{{ $asset->category }}</td>
                    <td class="px-5 py-4">
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-widest {{ $conditionColors[$asset->condition] }}">{{ $asset->condition }}</span>
                    </td>
                    <td class="px-5 py-4">
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-widest {{ $statusColors[$asset->status] }}">{{ str_replace('_', ' ', $asset->status) }}</span>
                    </td>
                    <td class="px-5 py-4 text-slate-600">
                        @if($asset->activeAssignment)
                            <span class="font-semibold">{{ $asset->activeAssignment->employee->name }}</span>
                            <p class="text-xs text-slate-400">Since {{ $asset->activeAssignment->assigned_date->format('M d, Y') }}</p>
                        @else
                            <span class="text-slate-400">—</span>
                        @endif
                    </td>
                    <td class="px-5 py-4">
                        <div class="flex items-center gap-1">
                            @can('assign assets')
                            @if($asset->isAvailable())
                            <button wire:click="openAssign({{ $asset->id }})" title="Assign" class="p-2 rounded-xl text-slate-400 hover:text-primary hover:bg-slate-50 transition-colors">
                                <span class="material-symbols-outlined text-sm">person_add</span>
                            </button>
                            @endif
                            @if($asset->isAssigned() && $asset->activeAssignment)
                            <button wire:click="openReturn({{ $asset->activeAssignment->id }})" title="Return" class="p-2 rounded-xl text-slate-400 hover:text-amber-500 hover:bg-slate-50 transition-colors">
                                <span class="material-symbols-outlined text-sm">assignment_return</span>
                            </button>
                            @endif
                            @endcan
                            <button wire:click="viewHistory({{ $asset->id }})" title="History" class="p-2 rounded-xl text-slate-400 hover:text-primary hover:bg-slate-50 transition-colors">
                                <span class="material-symbols-outlined text-sm">history</span>
                            </button>
                            @can('manage assets')
                            @if(!$asset->isAssigned())
                            <button wire:click="openEdit({{ $asset->id }})" title="Edit" class="p-2 rounded-xl text-slate-400 hover:text-primary hover:bg-slate-50 transition-colors">
                                <span class="material-symbols-outlined text-sm">edit</span>
                            </button>
                            @endif
                            @if($asset->status === 'available')
                            <button wire:click="retireAsset({{ $asset->id }})" wire:confirm="Retire this asset?" title="Retire" class="p-2 rounded-xl text-slate-400 hover:text-error hover:bg-slate-50 transition-colors">
                                <span class="material-symbols-outlined text-sm">archive</span>
                            </button>
                            @endif
                            @endcan
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="py-16 text-center text-slate-400">
                        <span class="material-symbols-outlined text-5xl block mb-3">inventory_2</span>
                        <p>No assets found.</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Mobile Cards --}}
    <div class="md:hidden space-y-3">
        @forelse($assets as $asset)
        @php
            $statusColors = [
                'available'         => 'bg-green-100 text-green-700',
                'assigned'          => 'bg-blue-100 text-blue-700',
                'under_maintenance' => 'bg-amber-100 text-amber-700',
                'retired'           => 'bg-slate-100 text-slate-500',
            ];
            $conditionColors = [
                'new'     => 'bg-emerald-100 text-emerald-700',
                'good'    => 'bg-green-100 text-green-700',
                'fair'    => 'bg-amber-100 text-amber-700',
                'poor'    => 'bg-orange-100 text-orange-700',
                'damaged' => 'bg-red-100 text-red-700',
            ];
        @endphp
        <div class="glass-card rounded-[1.5rem] p-4">
            <div class="flex items-start justify-between">
                <div>
                    <p class="font-bold text-slate-800">{{ $asset->name }}</p>
                    <p class="text-xs text-slate-400">{{ $asset->asset_tag }} · {{ $asset->brand }} {{ $asset->model }}</p>
                </div>
                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-widest {{ $statusColors[$asset->status] }}">{{ str_replace('_', ' ', $asset->status) }}</span>
            </div>
            <div class="mt-3 grid grid-cols-2 gap-2 text-xs">
                <div class="bg-slate-50 rounded-lg p-2">
                    <p class="text-slate-400">Category</p>
                    <p class="font-semibold text-slate-700 capitalize">{{ $asset->category }}</p>
                </div>
                <div class="bg-slate-50 rounded-lg p-2">
                    <p class="text-slate-400">Condition</p>
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-widest {{ $conditionColors[$asset->condition] }}">{{ $asset->condition }}</span>
                </div>
                @if($asset->activeAssignment)
                <div class="col-span-2 bg-blue-50 rounded-lg p-2">
                    <p class="text-slate-400">Assigned to</p>
                    <p class="font-semibold text-slate-700">{{ $asset->activeAssignment->employee->name }}</p>
                </div>
                @endif
            </div>
            <div class="mt-3 flex items-center gap-2 flex-wrap">
                @can('assign assets')
                @if($asset->isAvailable())
                <button wire:click="openAssign({{ $asset->id }})"
                    class="flex-1 text-center text-xs font-bold py-2 rounded-xl bg-primary/10 text-primary hover:bg-primary/20 transition-colors">
                    Assign
                </button>
                @endif
                @if($asset->isAssigned() && $asset->activeAssignment)
                <button wire:click="openReturn({{ $asset->activeAssignment->id }})"
                    class="flex-1 text-center text-xs font-bold py-2 rounded-xl bg-amber-100 text-amber-700 hover:bg-amber-200 transition-colors">
                    Return
                </button>
                @endif
                @endcan
                <button wire:click="viewHistory({{ $asset->id }})"
                    class="p-2 rounded-xl text-slate-400 hover:text-primary hover:bg-slate-50 transition-colors">
                    <span class="material-symbols-outlined text-sm">history</span>
                </button>
                @can('manage assets')
                @if(!$asset->isAssigned())
                <button wire:click="openEdit({{ $asset->id }})"
                    class="p-2 rounded-xl text-slate-400 hover:text-primary hover:bg-slate-50 transition-colors">
                    <span class="material-symbols-outlined text-sm">edit</span>
                </button>
                @endif
                @endcan
            </div>
        </div>
        @empty
        <div class="glass-card rounded-[2rem] py-16 text-center text-slate-400">
            <span class="material-symbols-outlined text-5xl block mb-3">inventory_2</span>
            <p>No assets found.</p>
        </div>
        @endforelse
    </div>

    <div class="mt-4">{{ $assets->links() }}</div>

    {{-- ── Create / Edit Asset Modal ── --}}
    @if($showAssetModal)
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm p-4">
        <div class="bg-white rounded-[2rem] shadow-2xl w-full max-w-lg p-6 md:p-8 max-h-[90vh] overflow-y-auto">
            <h3 class="text-xl md:text-2xl font-bold font-headline text-slate-900 mb-6">{{ $editingAsset ? 'Edit Asset' : 'New Asset' }}</h3>
            <form wire:submit="saveAsset" class="space-y-4">
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1">Asset Name *</label>
                    <input wire:model="name" type="text" class="w-full px-4 py-2 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-primary/20 focus:outline-none"/>
                    @error('name') <p class="text-error text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1">Category *</label>
                        <input wire:model="category" type="text" placeholder="e.g. Laptop, Phone" class="w-full px-4 py-2 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-primary/20 focus:outline-none"/>
                        @error('category') <p class="text-error text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1">Condition *</label>
                        <select wire:model="condition" class="w-full px-4 py-2 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-primary/20 focus:outline-none">
                            <option value="new">New</option>
                            <option value="good">Good</option>
                            <option value="fair">Fair</option>
                            <option value="poor">Poor</option>
                            <option value="damaged">Damaged</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1">Brand</label>
                        <input wire:model="brand" type="text" class="w-full px-4 py-2 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-primary/20 focus:outline-none"/>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1">Model</label>
                        <input wire:model="model" type="text" class="w-full px-4 py-2 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-primary/20 focus:outline-none"/>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1">Serial Number</label>
                        <input wire:model="serial_number" type="text" class="w-full px-4 py-2 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-primary/20 focus:outline-none"/>
                        @error('serial_number') <p class="text-error text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1">Purchase Date</label>
                        <input wire:model="purchase_date" type="date" class="w-full px-4 py-2 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-primary/20 focus:outline-none"/>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1">Purchase Cost</label>
                        <input wire:model="purchase_cost" type="number" step="0.01" min="0" class="w-full px-4 py-2 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-primary/20 focus:outline-none"/>
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1">Notes</label>
                    <textarea wire:model="asset_notes" rows="2" class="w-full px-4 py-2 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-primary/20 focus:outline-none"></textarea>
                </div>
                <div class="flex gap-3 pt-2">
                    <button type="submit" class="flex-1 bg-primary text-on-primary py-2.5 rounded-xl font-bold font-headline hover:opacity-90 transition-opacity">
                        {{ $editingAsset ? 'Update' : 'Create' }}
                    </button>
                    <button type="button" wire:click="$set('showAssetModal', false)" class="flex-1 bg-slate-100 text-slate-700 py-2.5 rounded-xl font-bold font-headline hover:bg-slate-200 transition-colors">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>
    @endif

    {{-- ── Assign Modal ── --}}
    @if($showAssignModal)
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm p-4">
        <div class="bg-white rounded-[2rem] shadow-2xl w-full max-w-md p-6 md:p-8">
            <h3 class="text-xl md:text-2xl font-bold font-headline text-slate-900 mb-6">Assign Asset</h3>
            <form wire:submit="saveAssignment" class="space-y-4">
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1">Assign To *</label>
                    <select wire:model="assign_user_id" class="w-full px-4 py-2 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-primary/20 focus:outline-none">
                        <option value="">— Select Employee —</option>
                        @foreach($employees as $emp)
                            <option value="{{ $emp->id }}">{{ $emp->name }}{{ $emp->position ? ' · ' . $emp->position : '' }}</option>
                        @endforeach
                    </select>
                    @error('assign_user_id') <p class="text-error text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1">Assigned Date *</label>
                        <input wire:model="assigned_date" type="date" class="w-full px-4 py-2 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-primary/20 focus:outline-none"/>
                        @error('assigned_date') <p class="text-error text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1">Expected Return</label>
                        <input wire:model="expected_return_date" type="date" class="w-full px-4 py-2 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-primary/20 focus:outline-none"/>
                        @error('expected_return_date') <p class="text-error text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1">Notes</label>
                    <textarea wire:model="assign_notes" rows="2" class="w-full px-4 py-2 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-primary/20 focus:outline-none"></textarea>
                </div>
                <div class="flex gap-3 pt-2">
                    <button type="submit" class="flex-1 bg-primary text-on-primary py-2.5 rounded-xl font-bold font-headline hover:opacity-90 transition-opacity">Assign</button>
                    <button type="button" wire:click="$set('showAssignModal', false)" class="flex-1 bg-slate-100 text-slate-700 py-2.5 rounded-xl font-bold font-headline hover:bg-slate-200 transition-colors">Cancel</button>
                </div>
            </form>
        </div>
    </div>
    @endif

    {{-- ── Return Modal ── --}}
    @if($showReturnModal)
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm p-4">
        <div class="bg-white rounded-[2rem] shadow-2xl w-full max-w-md p-6 md:p-8">
            <h3 class="text-xl md:text-2xl font-bold font-headline text-slate-900 mb-6">Record Asset Return</h3>
            <form wire:submit="saveReturn" class="space-y-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1">Return Date *</label>
                        <input wire:model="return_date" type="date" class="w-full px-4 py-2 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-primary/20 focus:outline-none"/>
                        @error('return_date') <p class="text-error text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1">Condition on Return *</label>
                        <select wire:model="condition_on_return" class="w-full px-4 py-2 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-primary/20 focus:outline-none">
                            <option value="good">Good</option>
                            <option value="fair">Fair</option>
                            <option value="poor">Poor</option>
                            <option value="damaged">Damaged</option>
                        </select>
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1">Return Notes</label>
                    <textarea wire:model="return_notes" rows="2" class="w-full px-4 py-2 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-primary/20 focus:outline-none"></textarea>
                </div>
                <div class="flex gap-3 pt-2">
                    <button type="submit" class="flex-1 bg-amber-500 text-white py-2.5 rounded-xl font-bold font-headline hover:opacity-90 transition-opacity">Record Return</button>
                    <button type="button" wire:click="$set('showReturnModal', false)" class="flex-1 bg-slate-100 text-slate-700 py-2.5 rounded-xl font-bold font-headline hover:bg-slate-200 transition-colors">Cancel</button>
                </div>
            </form>
        </div>
    </div>
    @endif

    {{-- ── History Modal ── --}}
    @if($showHistoryModal && $historyAsset)
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm p-4">
        <div class="bg-white rounded-[2rem] shadow-2xl w-full max-w-2xl p-6 md:p-8 max-h-[90vh] overflow-y-auto">
            <div class="flex items-start justify-between mb-6">
                <div>
                    <h3 class="text-xl md:text-2xl font-bold font-headline text-slate-900">{{ $historyAsset->name }}</h3>
                    <p class="text-sm text-slate-500">{{ $historyAsset->asset_tag }} · Assignment History</p>
                </div>
                <button wire:click="$set('showHistoryModal', false)" class="p-1 rounded-lg text-slate-400 hover:text-slate-700 hover:bg-slate-100 transition-colors">
                    <span class="material-symbols-outlined">close</span>
                </button>
            </div>

            @forelse($historyAsset->assignments as $a)
            @php
                $integrityOk = $a->integrity_hash ? $a->verifyIntegrity() : null;
            @endphp
            <div class="border border-slate-100 rounded-2xl p-4 mb-3">
                <div class="flex items-center justify-between mb-2">
                    <div class="flex items-center gap-2 flex-wrap">
                        <span class="font-bold text-slate-800">{{ $a->employee->name }}</span>
                        @if($a->status === 'active')
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-blue-100 text-blue-700">Active</span>
                        @else
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-500">Returned</span>
                        @endif
                        @if($integrityOk === true)
                            <span title="Record integrity verified" class="flex items-center gap-0.5 text-[10px] font-bold text-green-600">
                                <span class="material-symbols-outlined text-[12px]">verified</span> Verified
                            </span>
                        @elseif($integrityOk === false)
                            <span title="Integrity check FAILED" class="flex items-center gap-0.5 text-[10px] font-bold text-red-600">
                                <span class="material-symbols-outlined text-[12px]">gpp_bad</span> Tampered
                            </span>
                        @endif
                    </div>
                    <p class="text-xs text-slate-400">{{ $a->assigned_date->format('M d, Y') }}</p>
                </div>
                <div class="text-xs text-slate-500 space-y-0.5">
                    <p>Assigned by: <span class="font-semibold text-slate-700">{{ $a->assignedBy->name }}</span></p>
                    @if($a->expected_return_date)
                        <p>Expected return: {{ $a->expected_return_date->format('M d, Y') }}</p>
                    @endif
                    @if($a->actual_return_date)
                        <p>Returned: {{ $a->actual_return_date->format('M d, Y') }} · Condition: {{ ucfirst($a->condition_on_return) }}</p>
                        @if($a->returnedTo) <p>Received by: {{ $a->returnedTo->name }}</p> @endif
                    @endif
                    @if($a->notes) <p>Notes: {{ $a->notes }}</p> @endif
                    @if($a->return_notes) <p>Return notes: {{ $a->return_notes }}</p> @endif
                </div>
            </div>
            @empty
            <p class="text-slate-400 text-sm text-center py-8">No assignment history yet.</p>
            @endforelse
        </div>
    </div>
    @endif
</div>

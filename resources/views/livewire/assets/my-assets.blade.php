<div>
    <div class="flex items-center justify-between mb-8">
        <div>
            <h2 class="text-3xl font-extrabold text-slate-900 font-headline">My Assets</h2>
            <p class="text-slate-500 text-sm mt-1">Assets currently assigned to you</p>
        </div>
    </div>

    {{-- Tabs --}}
    <div class="flex gap-2 mb-6">
        <button wire:click="$set('tab', 'active')"
            class="px-5 py-2 rounded-xl font-bold text-sm font-headline transition-colors {{ $tab === 'active' ? 'bg-primary text-on-primary shadow' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
            Active
        </button>
        <button wire:click="$set('tab', 'history')"
            class="px-5 py-2 rounded-xl font-bold text-sm font-headline transition-colors {{ $tab === 'history' ? 'bg-primary text-on-primary shadow' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
            Return History
        </button>
    </div>

    <div class="space-y-4">
        @forelse($assignments as $a)
        @php
            $conditionColors = [
                'new'     => 'bg-emerald-100 text-emerald-700',
                'good'    => 'bg-green-100 text-green-700',
                'fair'    => 'bg-amber-100 text-amber-700',
                'poor'    => 'bg-orange-100 text-orange-700',
                'damaged' => 'bg-red-100 text-red-700',
            ];
        @endphp
        <div class="glass-card rounded-[1.5rem] p-5 flex items-center gap-5">
            <div class="w-12 h-12 rounded-2xl bg-primary/10 flex items-center justify-center text-primary flex-shrink-0">
                <span class="material-symbols-outlined">inventory_2</span>
            </div>
            <div class="flex-1 min-w-0">
                <p class="font-bold text-slate-800">{{ $a->asset->name }}</p>
                <p class="text-xs text-slate-400 mt-0.5">
                    {{ $a->asset->asset_tag }}
                    @if($a->asset->brand) · {{ $a->asset->brand }} @endif
                    @if($a->asset->model) {{ $a->asset->model }} @endif
                </p>
                <div class="flex items-center gap-2 mt-1.5">
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-widest {{ $conditionColors[$a->asset->condition] }}">{{ $a->asset->condition }}</span>
                    @if($a->isOverdue())
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-red-100 text-red-600">OVERDUE</span>
                    @endif
                </div>
            </div>
            <div class="text-right text-xs text-slate-500 flex-shrink-0">
                <p>Assigned <span class="font-semibold text-slate-700">{{ $a->assigned_date->format('M d, Y') }}</span></p>
                <p>By {{ $a->assignedBy->name }}</p>
                @if($a->expected_return_date)
                    <p class="mt-1">Return by <span class="{{ $a->isOverdue() ? 'text-red-600 font-bold' : 'font-semibold text-slate-700' }}">{{ $a->expected_return_date->format('M d, Y') }}</span></p>
                @endif
                @if($a->actual_return_date)
                    <p class="mt-1 text-green-600 font-semibold">Returned {{ $a->actual_return_date->format('M d, Y') }}</p>
                @endif
            </div>
        </div>
        @empty
        <div class="glass-card rounded-[2rem] py-20 text-center text-slate-400">
            <span class="material-symbols-outlined text-5xl block mb-3">inventory_2</span>
            <p>{{ $tab === 'active' ? 'No assets currently assigned to you.' : 'No return history.' }}</p>
        </div>
        @endforelse
    </div>

    <div class="mt-4">{{ $assignments->links() }}</div>
</div>

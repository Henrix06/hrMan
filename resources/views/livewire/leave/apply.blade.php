<div>
    @if (session()->has('message'))
        <div class="mb-6 px-4 py-3 rounded-xl bg-green-50 border border-green-200 text-green-700 text-sm flex items-center gap-2">
            <span class="material-symbols-outlined text-sm">check_circle</span>
            {{ session('message') }}
        </div>
    @endif

    <div class="mb-6">
        <h2 class="text-2xl md:text-3xl font-extrabold text-slate-900 font-headline">Apply for Leave</h2>
        <p class="text-slate-500 text-sm mt-1">Submit a time-off request for approval</p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 lg:gap-8">

        {{-- Form --}}
        <div class="lg:col-span-2">
            <div class="glass-card rounded-[2rem] p-8">
                <form wire:submit="submit" class="space-y-6">

                    {{-- Leave Type --}}
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-3">Leave Type</label>
                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                            @foreach($this->leaveTypes as $type)
                            @php
                                $balance = $this->leaveBalances->get($type->id);
                                $remaining = $balance?->remaining_days ?? $type->default_days;
                                $selected = (string)$leave_type_id === (string)$type->id;
                            @endphp
                            <label class="relative flex flex-col gap-1 p-4 rounded-2xl border-2 cursor-pointer transition-all
                                {{ $selected ? 'border-primary bg-primary/5' : 'border-slate-200 hover:border-slate-300 bg-white' }}
                                {{ $remaining <= 0 ? 'opacity-50' : '' }}">
                                <input wire:model.live="leave_type_id" type="radio" value="{{ $type->id }}"
                                    class="sr-only" {{ $remaining <= 0 ? 'disabled' : '' }}/>
                                <span class="text-sm font-bold {{ $selected ? 'text-primary' : 'text-slate-800' }}">{{ $type->name }}</span>
                                <span class="text-xs {{ $remaining > 0 ? 'text-slate-500' : 'text-error font-semibold' }}">
                                    {{ $remaining }} day{{ $remaining !== 1 ? 's' : '' }} left
                                </span>
                                @if($selected)
                                <span class="absolute top-2 right-2 w-4 h-4 bg-primary rounded-full flex items-center justify-center">
                                    <span class="material-symbols-outlined text-white text-[10px]">check</span>
                                </span>
                                @endif
                            </label>
                            @endforeach
                        </div>
                        @error('leave_type_id') <p class="text-error text-xs mt-2">{{ $message }}</p> @enderror
                    </div>

                    {{-- Date Range --}}
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-1">Start Date</label>
                            <input wire:model.live="start_date" type="date"
                                min="{{ now()->toDateString() }}"
                                class="w-full px-4 py-2.5 rounded-xl border {{ $errors->has('start_date') ? 'border-error' : 'border-slate-200' }} text-sm focus:ring-2 focus:ring-primary/20 focus:outline-none bg-white"/>
                            @error('start_date') <p class="text-error text-xs mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-1">End Date</label>
                            <input wire:model.live="end_date" type="date"
                                min="{{ $start_date ?: now()->toDateString() }}"
                                class="w-full px-4 py-2.5 rounded-xl border {{ $errors->has('end_date') ? 'border-error' : 'border-slate-200' }} text-sm focus:ring-2 focus:ring-primary/20 focus:outline-none bg-white"/>
                            @error('end_date') <p class="text-error text-xs mt-1">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    {{-- Duration pill --}}
                    @if($total_days > 0)
                    <div class="flex items-center gap-3 px-4 py-3 rounded-xl bg-primary/5 border border-primary/20">
                        <span class="material-symbols-outlined text-primary text-sm">event_available</span>
                        <p class="text-sm font-bold text-primary">
                            {{ $total_days }} working day{{ $total_days !== 1 ? 's' : '' }}
                            <span class="font-normal text-slate-500">(weekends excluded)</span>
                        </p>
                    </div>
                    @elseif($start_date && $end_date)
                    <div class="flex items-center gap-3 px-4 py-3 rounded-xl bg-amber-50 border border-amber-200">
                        <span class="material-symbols-outlined text-amber-600 text-sm">warning</span>
                        <p class="text-sm text-amber-700">Selected range falls entirely on weekends.</p>
                    </div>
                    @endif
                    @error('total_days') <p class="text-error text-xs -mt-2">{{ $message }}</p> @enderror

                    {{-- Reason --}}
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1">
                            Reason <span class="text-slate-400 font-normal">(optional)</span>
                        </label>
                        <textarea wire:model="reason" rows="3" placeholder="Briefly describe the reason for your leave..."
                            class="w-full px-4 py-2.5 rounded-xl border {{ $errors->has('reason') ? 'border-error' : 'border-slate-200' }} text-sm focus:ring-2 focus:ring-primary/20 focus:outline-none bg-white resize-none"></textarea>
                        @error('reason') <p class="text-error text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    {{-- Actions --}}
                    <div class="flex items-center gap-3 pt-2">
                        <button type="submit"
                            class="flex-1 bg-gradient-to-br from-primary to-primary-container text-on-primary py-3 rounded-xl font-bold font-headline shadow-lg shadow-primary/20 hover:opacity-90 transition-opacity flex items-center justify-center gap-2"
                            wire:loading.attr="disabled" wire:target="submit">
                            <span wire:loading.remove wire:target="submit" class="material-symbols-outlined text-sm">send</span>
                            <span wire:loading wire:target="submit" class="material-symbols-outlined text-sm animate-spin">progress_activity</span>
                            <span wire:loading.remove wire:target="submit">Submit Request</span>
                            <span wire:loading wire:target="submit">Submitting...</span>
                        </button>
                        <button type="button" wire:click="$set('leave_type_id', '')"
                            class="px-6 py-3 rounded-xl bg-slate-100 text-slate-600 font-bold font-headline hover:bg-slate-200 transition-colors">
                            Clear
                        </button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Leave Balance Sidebar --}}
        <div class="space-y-4">
            <div class="glass-card rounded-[2rem] p-6">
                <h3 class="text-base font-bold font-headline text-slate-900 mb-4">Your Leave Balance</h3>
                <div class="space-y-3">
                    @foreach($this->leaveTypes as $type)
                    @php
                        $balance = $this->leaveBalances->get($type->id);
                        $available = $balance?->days_available ?? $type->default_days;
                        $taken = $balance?->days_taken ?? 0;
                        $pending = $balance?->days_pending ?? 0;
                        $remaining = $balance?->remaining_days ?? $available;
                        $pct = $available > 0 ? round(($taken / $available) * 100) : 0;
                        $isSelected = (string)$leave_type_id === (string)$type->id;
                    @endphp
                    <div class="p-3 rounded-xl {{ $isSelected ? 'bg-primary/5 border border-primary/20' : 'bg-slate-50' }} transition-colors">
                        <div class="flex items-center justify-between mb-1.5">
                            <span class="text-sm font-semibold {{ $isSelected ? 'text-primary' : 'text-slate-700' }}">{{ $type->name }}</span>
                            <span class="text-xs font-bold {{ $remaining > 0 ? 'text-slate-600' : 'text-error' }}">
                                {{ $remaining }}/{{ $available }}
                            </span>
                        </div>
                        <div class="w-full bg-slate-200 rounded-full h-1.5">
                            <div class="h-1.5 rounded-full transition-all {{ $pct >= 100 ? 'bg-error' : ($pct >= 75 ? 'bg-amber-500' : 'bg-primary') }}"
                                style="width: {{ min($pct, 100) }}%"></div>
                        </div>
                        @if($pending > 0)
                        <p class="text-[10px] text-amber-600 font-semibold mt-1">{{ $pending }} day{{ $pending !== 1 ? 's' : '' }} pending approval</p>
                        @endif
                    </div>
                    @endforeach
                </div>
            </div>

            <div class="glass-card rounded-[2rem] p-5 bg-gradient-to-br from-primary/5 to-white">
                <div class="flex items-start gap-3">
                    <span class="material-symbols-outlined text-primary text-sm mt-0.5">info</span>
                    <div class="text-xs text-slate-500 space-y-1">
                        <p>Weekends are automatically excluded from your leave count.</p>
                        <p>Requests require manager approval before they are confirmed.</p>
                        <a href="{{ route('leave.balance') }}" wire:navigate class="text-primary font-bold hover:underline block mt-2">View full balance →</a>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

@php
    $currentPlan = $tenant?->plan;
    $confirming  = $confirmingPlan ? config("pricing.plans.{$confirmingPlan}") : null;
    $confirmDiff = $confirming ? $this->diffFor($confirmingPlan, $currentPlan) : ['added' => [], 'removed' => []];
    $confirmLimit = $confirming['employee_limit'] ?? null;
@endphp

<div>
    <div class="mb-8">
        <h2 class="text-3xl font-extrabold text-slate-900 font-headline">Plan &amp; Modules</h2>
        <p class="text-slate-500 text-sm mt-1">Change your plan any time — the features in your new plan switch on immediately.</p>
    </div>

    @if($status_message)
    <div class="mb-6 px-4 py-3 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-700 text-sm flex items-start gap-2">
        <span class="material-symbols-outlined text-sm mt-0.5">check_circle</span>
        <span>{{ $status_message }}</span>
    </div>
    @endif

    @if($error_message)
    <div class="mb-6 px-4 py-3 rounded-xl bg-red-50 border border-red-200 text-red-700 text-sm flex items-center gap-2">
        <span class="material-symbols-outlined text-sm">error</span>
        {{ $error_message }}
    </div>
    @endif

    {{-- Current plan --}}
    <div class="glass-card p-6 rounded-[2rem] mb-8">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-5">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 rounded-2xl bg-primary/10 flex items-center justify-center text-primary flex-shrink-0">
                    <span class="material-symbols-outlined">workspace_premium</span>
                </div>
                <div>
                    <p class="text-xs font-semibold text-slate-500 uppercase tracking-widest">Current plan</p>
                    <h3 class="text-xl font-extrabold font-headline text-slate-900">{{ $planConfig['name'] ?? 'Free' }}</h3>
                    <p class="text-xs text-slate-400">{{ count($planConfig['modules'] ?? []) }} modules enabled</p>
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-3">
                <div class="px-4 py-2.5 rounded-xl bg-slate-50">
                    <p class="text-[10px] font-semibold text-slate-400 uppercase tracking-widest">Employees</p>
                    <p class="text-sm font-bold text-slate-700">
                        {{ $employeeCount }}@if(($planConfig['employee_limit'] ?? null) !== null) / {{ $planConfig['employee_limit'] }}@endif
                    </p>
                </div>

                <div class="px-4 py-2.5 rounded-xl {{ $accessEndsAt ? ($isOnTrial ? 'bg-amber-50' : 'bg-emerald-50') : 'bg-slate-50' }}">
                    <p class="text-[10px] font-semibold text-slate-400 uppercase tracking-widest">
                        {{ $isOnTrial ? 'Trial ends' : ($accessEndsAt ? 'Access until' : 'Access') }}
                    </p>
                    <p class="text-sm font-bold {{ $accessEndsAt ? ($isOnTrial ? 'text-amber-700' : 'text-emerald-700') : 'text-slate-700' }}">
                        {{ $accessEndsAt ? $accessEndsAt->format('j M Y') : 'No expiry' }}
                    </p>
                </div>

                <a href="{{ route('license.index') }}"
                    class="px-4 py-2.5 rounded-xl border border-slate-200 text-sm font-semibold text-slate-600 hover:border-primary/40 hover:text-primary transition-colors flex items-center gap-2">
                    <span class="material-symbols-outlined text-[18px]">key</span>
                    Redeem a code
                </a>
            </div>
        </div>
    </div>

    {{-- Billing toggle --}}
    <div class="flex items-center justify-between mb-6">
        <h3 class="text-lg font-bold font-headline text-slate-900">Available plans</h3>

        <div class="inline-flex items-center bg-slate-100 rounded-2xl p-1 gap-1">
            <button type="button" wire:click="toggleBilling('monthly')"
                class="px-4 py-2 rounded-xl text-sm font-medium transition-all duration-200 {{ $billing === 'monthly' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500 hover:text-slate-700' }}">
                Monthly
            </button>
            <button type="button" wire:click="toggleBilling('yearly')"
                class="px-4 py-2 rounded-xl text-sm font-medium transition-all duration-200 flex items-center gap-2 {{ $billing === 'yearly' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500 hover:text-slate-700' }}">
                Yearly
                <span class="bg-emerald-100 text-emerald-700 text-[10px] font-bold px-2 py-0.5 rounded-full">20% off</span>
            </button>
        </div>
    </div>

    {{-- Plan grid --}}
    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-6">
        @foreach($plans as $key => $plan)
        @php
            $isCurrent  = $key === $currentPlan;
            $isHighlighted = $plan['highlighted'] && ! $isCurrent;
            $diff       = $this->diffFor($key, $currentPlan);
            $selfServe  = $plan['monthly_price'] !== null;
            $limit      = $plan['employee_limit'];
            $overLimit  = $limit !== null && $employeeCount > $limit;
        @endphp

        <div class="relative flex flex-col rounded-3xl border transition-all duration-200
            {{ $isCurrent
                ? 'bg-slate-900 border-slate-900 shadow-2xl shadow-slate-900/20 text-white'
                : ($isHighlighted
                    ? 'bg-primary border-primary shadow-2xl shadow-primary/20 text-white'
                    : 'bg-white border-slate-200 hover:border-primary/40 hover:shadow-lg') }}">

            @if($isCurrent)
            <div class="absolute -top-3.5 left-1/2 -translate-x-1/2">
                <span class="bg-white text-slate-900 text-[11px] font-bold px-3 py-1 rounded-full shadow-sm whitespace-nowrap">Your plan</span>
            </div>
            @elseif($plan['badge'])
            <div class="absolute -top-3.5 left-1/2 -translate-x-1/2">
                <span class="bg-amber-400 text-amber-900 text-[11px] font-bold px-3 py-1 rounded-full shadow-sm whitespace-nowrap">{{ $plan['badge'] }}</span>
            </div>
            @endif

            <div class="p-7 flex flex-col flex-1">
                <h4 class="font-headline font-bold text-xl mb-1 {{ $isCurrent || $isHighlighted ? 'text-white' : 'text-slate-900' }}">{{ $plan['name'] }}</h4>
                <p class="text-sm mb-5 {{ $isCurrent || $isHighlighted ? 'text-white/70' : 'text-slate-500' }}">{{ $plan['description'] }}</p>

                <div class="font-headline font-black text-3xl mb-1 {{ $isCurrent || $isHighlighted ? 'text-white' : 'text-slate-900' }}">
                    {{ $this->price($plan) }}
                </div>
                <p class="text-xs mb-5 {{ $isCurrent || $isHighlighted ? 'text-white/60' : 'text-slate-400' }}">
                    @if($plan['monthly_price'] === null)
                        Tailored pricing
                    @elseif($plan['monthly_price'] === 0)
                        forever free
                    @elseif($billing === 'yearly')
                        billed ${{ $plan['yearly_price'] }}/year
                    @else
                        per month
                    @endif
                </p>

                <div class="flex items-center gap-2 mb-5 pb-5 border-b {{ $isCurrent || $isHighlighted ? 'border-white/20' : 'border-slate-100' }}">
                    <span class="material-symbols-outlined text-[18px] {{ $isCurrent || $isHighlighted ? 'text-white/80' : 'text-primary' }}">groups</span>
                    <span class="text-sm {{ $isCurrent || $isHighlighted ? 'text-white/90' : 'text-slate-700' }}">
                        {{ $limit ? 'Up to ' . $limit . ' employees' : 'Unlimited employees' }}
                    </span>
                </div>

                {{-- Module list --}}
                <ul class="space-y-2 flex-1 mb-6">
                    @foreach($modules as $moduleKey => $moduleLabel)
                    @php $included = in_array($moduleKey, $plan['modules'], true); @endphp
                    <li class="flex items-center gap-2 text-sm">
                        @if($included)
                            <span class="material-symbols-outlined filled text-[16px] flex-shrink-0 {{ $isCurrent || $isHighlighted ? 'text-white' : 'text-primary' }}">check_circle</span>
                            <span class="{{ $isCurrent || $isHighlighted ? 'text-white/90' : 'text-slate-700' }}">{{ $moduleLabel }}</span>
                        @else
                            <span class="material-symbols-outlined text-[16px] flex-shrink-0 {{ $isCurrent || $isHighlighted ? 'text-white/25' : 'text-slate-200' }}">remove</span>
                            <span class="{{ $isCurrent || $isHighlighted ? 'text-white/25' : 'text-slate-300' }}">{{ $moduleLabel }}</span>
                        @endif
                    </li>
                    @endforeach
                </ul>

                @if($isCurrent)
                    <span class="block text-center py-3 rounded-2xl text-sm font-semibold bg-white/10 text-white/60 cursor-default">Current plan</span>
                @elseif(! $selfServe)
                    <a href="{{ route('pricing') }}"
                        class="block text-center py-3 rounded-2xl text-sm font-semibold bg-primary text-white hover:bg-primary-dim transition-all">Contact us</a>
                @else
                    @if($overLimit)
                    <p class="mb-3 text-[11px] font-semibold px-3 py-2 rounded-xl {{ $isHighlighted ? 'bg-white/10 text-white/90' : 'bg-amber-50 text-amber-700' }}">
                        You have {{ $employeeCount }} employees; this plan allows {{ $limit }}.
                    </p>
                    @endif
                    <button type="button" wire:click="confirm('{{ $key }}')"
                        class="block w-full text-center py-3 rounded-2xl text-sm font-semibold transition-all duration-200
                        {{ $isHighlighted ? 'bg-white text-primary hover:bg-slate-50' : 'bg-primary text-white hover:bg-primary-dim' }}">
                        @if($diff['removed'] && ! $diff['added'])
                            Downgrade to {{ $plan['name'] }}
                        @elseif($diff['added'] && ! $diff['removed'])
                            Upgrade to {{ $plan['name'] }}
                        @else
                            Switch to {{ $plan['name'] }}
                        @endif
                    </button>
                @endif
            </div>
        </div>
        @endforeach
    </div>

    {{-- Confirmation overlay --}}
    @if($confirming)
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-sm">
        <div class="bg-white rounded-3xl shadow-2xl max-w-lg w-full p-8">
            <div class="flex items-start gap-4 mb-6">
                <div class="w-12 h-12 rounded-2xl bg-primary/10 flex items-center justify-center text-primary flex-shrink-0">
                    <span class="material-symbols-outlined">swap_horiz</span>
                </div>
                <div>
                    <h3 class="text-xl font-extrabold font-headline text-slate-900">
                        Switch to {{ $confirming['name'] }}?
                    </h3>
                    <p class="text-sm text-slate-500 mt-0.5">
                        You're moving from {{ $planConfig['name'] ?? 'Free' }} to {{ $confirming['name'] }}.
                    </p>
                </div>
            </div>

            <div class="space-y-3 mb-6">
                @if($confirmDiff['added'])
                <div class="flex items-start gap-3 p-3 rounded-xl bg-emerald-50">
                    <span class="material-symbols-outlined text-emerald-600 text-sm mt-0.5">add_circle</span>
                    <div>
                        <p class="text-xs font-bold text-emerald-800 uppercase tracking-wide">Modules unlocked</p>
                        <p class="text-sm text-emerald-700">
                            {{ collect($confirmDiff['added'])->map(fn ($k) => $modules[$k] ?? $k)->join(', ') }}
                        </p>
                    </div>
                </div>
                @endif

                @if($confirmDiff['removed'])
                <div class="flex items-start gap-3 p-3 rounded-xl bg-amber-50">
                    <span class="material-symbols-outlined text-amber-600 text-sm mt-0.5">warning</span>
                    <div>
                        <p class="text-xs font-bold text-amber-800 uppercase tracking-wide">Modules turned off</p>
                        <p class="text-sm text-amber-700">
                            {{ collect($confirmDiff['removed'])->map(fn ($k) => $modules[$k] ?? $k)->join(', ') }}
                        </p>
                        <p class="text-xs text-amber-600 mt-1">Your data is kept — it reappears if you switch back.</p>
                    </div>
                </div>
                @endif

                @if($confirming['monthly_price'] === 0)
                <div class="flex items-start gap-3 p-3 rounded-xl bg-slate-50">
                    <span class="material-symbols-outlined text-slate-500 text-sm mt-0.5">info</span>
                    <p class="text-sm text-slate-600">The Free plan never expires and has no trial.</p>
                </div>
                @elseif($accessEndsAt)
                <div class="flex items-start gap-3 p-3 rounded-xl bg-slate-50">
                    <span class="material-symbols-outlined text-slate-500 text-sm mt-0.5">schedule</span>
                    <p class="text-sm text-slate-600">Your current access until {{ $accessEndsAt->format('j M Y') }} is kept — you won't lose paid time.</p>
                </div>
                @else
                <div class="flex items-start gap-3 p-3 rounded-xl bg-slate-50">
                    <span class="material-symbols-outlined text-slate-500 text-sm mt-0.5">timer</span>
                    <p class="text-sm text-slate-600">A {{ config('pricing.trial_days', 14) }}-day trial starts immediately. No card required.</p>
                </div>
                @endif

                @if($confirmLimit !== null && $employeeCount > $confirmLimit)
                <div class="flex items-start gap-3 p-3 rounded-xl bg-red-50">
                    <span class="material-symbols-outlined text-red-500 text-sm mt-0.5">group_off</span>
                    <p class="text-sm text-red-700">
                        You currently have {{ $employeeCount }} employees and this plan allows {{ $confirmLimit }}.
                        Existing records are kept, but you'll be prompted to upgrade before adding more.
                    </p>
                </div>
                @endif
            </div>

            <div class="flex flex-col sm:flex-row gap-3">
                <button type="button" wire:click="cancel"
                    class="flex-1 px-5 py-3 rounded-2xl border border-slate-200 text-sm font-semibold text-slate-600 hover:border-slate-300 transition-colors">
                    Cancel
                </button>
                <button type="button" wire:click="switchPlan" wire:loading.attr="disabled"
                    class="flex-1 px-5 py-3 rounded-2xl bg-primary text-white text-sm font-bold hover:bg-primary-dim transition-colors disabled:opacity-60 flex items-center justify-center gap-2">
                    <span wire:loading.remove>Confirm switch</span>
                    <span wire:loading>Switching…</span>
                </button>
            </div>
        </div>
    </div>
    @endif
</div>

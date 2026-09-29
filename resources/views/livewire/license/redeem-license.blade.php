<div>
    <div class="mb-8">
        <h2 class="text-3xl font-extrabold text-slate-900 font-headline">Licence</h2>
        <p class="text-slate-500 text-sm mt-1">Enter a licence code to activate or upgrade your workspace.</p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- Redeem form --}}
        <div class="lg:col-span-2 space-y-6">

            @if($status_message)
            <div class="px-4 py-3 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-700 text-sm flex items-center gap-2">
                <span class="material-symbols-outlined text-sm">check_circle</span>
                {{ $status_message }}
            </div>
            @endif

            @if($error_message)
            <div class="px-4 py-3 rounded-xl bg-red-50 border border-red-200 text-red-700 text-sm flex items-center gap-2">
                <span class="material-symbols-outlined text-sm">error</span>
                {{ $error_message }}
            </div>
            @endif

            <div class="glass-card p-8 rounded-[2rem]">
                <div class="flex items-start gap-4 mb-6">
                    <div class="w-12 h-12 rounded-2xl bg-primary/10 flex items-center justify-center text-primary flex-shrink-0">
                        <span class="material-symbols-outlined">key</span>
                    </div>
                    <div>
                        <h3 class="text-lg font-bold font-headline text-slate-900">Redeem a code</h3>
                        <p class="text-sm text-slate-500">Codes are case-insensitive and hyphens are optional.</p>
                    </div>
                </div>

                <form wire:submit="redeem" class="space-y-4">
                    <div>
                            <label for="license-code" class="block text-xs font-semibold text-slate-500 uppercase tracking-widest mb-2">Licence code</label>
                        <div class="flex flex-col sm:flex-row gap-3">
                            <input id="license-code" wire:model="code" type="text" autocomplete="off" spellcheck="false"
                                placeholder="UMSIZI-XXXX-XXXX-XXXX"
                                class="flex-1 px-4 py-3 rounded-xl border border-slate-200 text-sm font-mono tracking-wider uppercase focus:ring-2 focus:ring-primary/20 focus:outline-none"/>
                            <button type="submit" wire:loading.attr="disabled"
                                class="px-6 py-3 rounded-xl bg-gradient-to-br from-primary to-primary-container text-white font-bold font-headline text-sm shadow-lg shadow-primary/20 hover:opacity-90 active:scale-[0.98] transition-all disabled:opacity-60 flex items-center justify-center gap-2">
                                <span wire:loading.remove class="flex items-center gap-2">
                                    <span class="material-symbols-outlined text-sm">redeem</span>
                                    Apply code
                                </span>
                                <span wire:loading class="flex items-center gap-2">
                                    <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/>
                                    </svg>
                                    Applying…
                                </span>
                            </button>
                        </div>
                        @error('code') <p class="mt-2 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                </form>
            </div>

            {{-- Redemption history --}}
            <div class="glass-card rounded-[2rem] overflow-hidden">
                <div class="p-6 border-b border-slate-100">
                    <h3 class="text-base font-bold font-headline text-slate-900">Redemption history</h3>
                    <p class="text-xs text-slate-400 mt-0.5">Licence codes applied to this workspace</p>
                </div>

                <table class="w-full text-sm">
                    <thead class="bg-slate-50/80">
                        <tr>
                            <th class="text-left px-6 py-4 font-semibold text-slate-500 uppercase tracking-wider text-xs">Code</th>
                            <th class="text-left px-6 py-4 font-semibold text-slate-500 uppercase tracking-wider text-xs">Plan</th>
                            <th class="text-left px-6 py-4 font-semibold text-slate-500 uppercase tracking-wider text-xs">Access until</th>
                            <th class="text-left px-6 py-4 font-semibold text-slate-500 uppercase tracking-wider text-xs">Redeemed</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($redemptions as $redemption)
                        <tr class="hover:bg-slate-50/50 transition-colors">
                            <td class="px-6 py-4 font-mono text-xs text-slate-600">{{ $redemption->licenseCode?->code ?? '—' }}</td>
                            <td class="px-6 py-4 font-semibold text-slate-700">{{ $redemption->planName() }}</td>
                            <td class="px-6 py-4 text-slate-600">
                                {{ $redemption->subscription_ends_at?->format('j M Y') ?? 'Perpetual' }}
                            </td>
                            <td class="px-6 py-4 text-slate-400 text-xs">{{ $redemption->created_at->format('j M Y H:i') }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="px-6 py-14 text-center text-slate-400">
                                <span class="material-symbols-outlined text-4xl block mb-2">key_off</span>
                                No licence codes have been redeemed yet.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Current status --}}
        <div class="space-y-6">
            <div class="glass-card p-6 rounded-[2rem]">
                <h3 class="text-base font-bold font-headline text-slate-900 mb-4">Current licence</h3>

                @if($tenant)
                <div class="space-y-3">
                    <div class="flex items-center justify-between p-3 rounded-xl bg-primary/5">
                        <div class="flex items-center gap-2">
                            <span class="material-symbols-outlined text-primary text-sm">business</span>
                            <span class="text-sm font-semibold text-slate-700">{{ $tenant->name }}</span>
                        </div>
                    </div>

                    <div class="flex items-center justify-between p-3 rounded-xl bg-slate-50">
                        <div class="flex items-center gap-2">
                            <span class="material-symbols-outlined text-slate-500 text-sm">star</span>
                            <span class="text-sm font-semibold text-slate-700">Plan</span>
                        </div>
                        <span class="text-sm font-extrabold text-slate-700">{{ $planConfig['name'] ?? ucfirst($tenant->plan) }}</span>
                    </div>

                    @if($tenant->subscription_ends_at && $tenant->subscription_ends_at->isFuture())
                    <div class="flex items-center justify-between p-3 rounded-xl bg-emerald-50">
                        <div class="flex items-center gap-2">
                            <span class="material-symbols-outlined text-emerald-600 text-sm">verified</span>
                            <span class="text-sm font-semibold text-slate-700">Active until</span>
                        </div>
                        <span class="text-sm font-extrabold text-emerald-700">{{ $tenant->subscription_ends_at->format('j M Y') }}</span>
                    </div>
                    @elseif($isOnTrial)
                    <div class="flex items-center justify-between p-3 rounded-xl bg-amber-50">
                        <div class="flex items-center gap-2">
                            <span class="material-symbols-outlined text-amber-600 text-sm">timer</span>
                            <span class="text-sm font-semibold text-slate-700">Trial ends</span>
                        </div>
                        <span class="text-sm font-extrabold text-amber-700">{{ $tenant->trial_ends_at->format('j M Y') }}</span>
                    </div>
                    @else
                    <div class="flex items-center justify-between p-3 rounded-xl bg-slate-50">
                        <div class="flex items-center gap-2">
                            <span class="material-symbols-outlined text-slate-400 text-sm">info</span>
                            <span class="text-sm font-semibold text-slate-700">No expiry</span>
                        </div>
                        <span class="text-xs font-semibold text-slate-500">Free plan</span>
                    </div>
                    @endif

                    <div class="flex items-center justify-between p-3 rounded-xl bg-slate-50">
                        <div class="flex items-center gap-2">
                            <span class="material-symbols-outlined text-slate-500 text-sm">widgets</span>
                            <span class="text-sm font-semibold text-slate-700">Modules</span>
                        </div>
                        <span class="text-sm font-extrabold text-slate-700">
                            {{ count($planConfig['modules'] ?? []) }} / {{ count(config('pricing.modules', [])) }}
                        </span>
                    </div>
                </div>
                @else
                <p class="text-sm text-slate-400">No workspace is linked to your account.</p>
                @endif
            </div>

            <div class="glass-card p-6 rounded-[2rem] bg-gradient-to-br from-primary/5 to-white">
                <h3 class="text-base font-bold font-headline text-slate-900 mb-3">Need a code?</h3>
                <p class="text-sm text-slate-500 leading-relaxed">
                    Licence codes come from your provider. If you don't have one, you can switch plans in-app and start a trial instead.
                </p>
                <a href="{{ route('plan.index') }}"
                    class="mt-4 inline-flex items-center gap-2 text-sm font-bold text-primary hover:underline">
                    Change your plan
                    <span class="material-symbols-outlined text-sm">arrow_forward</span>
                </a>
            </div>
        </div>
    </div>
</div>

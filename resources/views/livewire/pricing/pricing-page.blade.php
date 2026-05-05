<div x-data="{ billing: @entangle('billing') }">

    {{-- ── Hero ── --}}
    <section class="pt-20 pb-12 text-center px-6">
        <span class="inline-flex items-center gap-2 bg-primary/10 text-primary text-xs font-semibold px-3 py-1.5 rounded-full border border-primary/20 mb-6">
            <span class="w-1.5 h-1.5 rounded-full bg-primary"></span>
            Simple, transparent pricing
        </span>
        <h1 class="font-headline font-black text-5xl md:text-6xl text-slate-900 leading-tight tracking-tight mb-4">
            Pay for what<br/>your team <span class="text-primary italic">actually needs</span>
        </h1>
        <p class="text-slate-500 text-lg max-w-xl mx-auto mb-10">
            Start free, scale as you grow. Every plan includes core HR tools — upgrade to unlock more power.
        </p>

        {{-- Billing toggle --}}
        <div class="inline-flex items-center bg-slate-100 rounded-2xl p-1 gap-1">
            <button
                wire:click="toggleBilling('monthly')"
                class="px-5 py-2 rounded-xl text-sm font-medium transition-all duration-200"
                :class="billing === 'monthly' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500 hover:text-slate-700'"
            >Monthly</button>
            <button
                wire:click="toggleBilling('yearly')"
                class="px-5 py-2 rounded-xl text-sm font-medium transition-all duration-200 flex items-center gap-2"
                :class="billing === 'yearly' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500 hover:text-slate-700'"
            >
                Yearly
                <span class="bg-emerald-100 text-emerald-700 text-[10px] font-bold px-2 py-0.5 rounded-full">2 months free</span>
            </button>
        </div>
    </section>

    {{-- ── Pricing cards ── --}}
    <section class="max-w-7xl mx-auto px-6 pb-16">
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-6">

            @foreach($this->plans as $key => $plan)
            @php
                $isHighlighted = $plan['highlighted'];
                $price = $this->price($plan);
                $savings = $this->yearlySavings($plan);
                $allModules = $this->modules;
            @endphp

            <div class="relative flex flex-col rounded-3xl border transition-all duration-200
                {{ $isHighlighted
                    ? 'bg-primary border-primary shadow-2xl shadow-primary/20 text-white scale-[1.02]'
                    : 'bg-white border-slate-200 hover:border-primary/40 hover:shadow-lg' }}">

                {{-- Badge --}}
                @if($plan['badge'])
                <div class="absolute -top-3.5 left-1/2 -translate-x-1/2">
                    <span class="bg-amber-400 text-amber-900 text-[11px] font-bold px-3 py-1 rounded-full shadow-sm whitespace-nowrap">
                        {{ $plan['badge'] }}
                    </span>
                </div>
                @endif

                <div class="p-7 flex flex-col flex-1">

                    {{-- Plan name & description --}}
                    <div class="mb-6">
                        <h2 class="font-headline font-bold text-xl mb-1 {{ $isHighlighted ? 'text-white' : 'text-slate-900' }}">
                            {{ $plan['name'] }}
                        </h2>
                        <p class="text-sm {{ $isHighlighted ? 'text-white/70' : 'text-slate-500' }}">
                            {{ $plan['description'] }}
                        </p>
                    </div>

                    {{-- Price --}}
                    <div class="mb-2">
                        <div class="font-headline font-black text-4xl {{ $isHighlighted ? 'text-white' : 'text-slate-900' }}">
                            {{ $price }}
                        </div>
                        @if($billing === 'yearly' && $plan['monthly_price'] > 0)
                        <div class="text-xs mt-1 {{ $isHighlighted ? 'text-white/60' : 'text-slate-400' }}">
                            billed ${{ $plan['yearly_price'] }}/year
                        </div>
                        @elseif($plan['monthly_price'] > 0)
                        <div class="text-xs mt-1 {{ $isHighlighted ? 'text-white/60' : 'text-slate-400' }}">
                            per month
                        </div>
                        @else
                        <div class="text-xs mt-1 {{ $isHighlighted ? 'text-white/60' : 'text-slate-400' }}">
                            forever
                        </div>
                        @endif
                    </div>

                    {{-- Savings pill --}}
                    @if($savings && $billing === 'yearly')
                    <div class="mb-5">
                        <span class="inline-block bg-emerald-100 text-emerald-700 text-[11px] font-semibold px-2.5 py-1 rounded-full">
                            {{ $savings }}
                        </span>
                    </div>
                    @else
                    <div class="mb-5"></div>
                    @endif

                    {{-- Employee limit --}}
                    <div class="flex items-center gap-2 mb-5 pb-5 border-b {{ $isHighlighted ? 'border-white/20' : 'border-slate-100' }}">
                        <span class="material-symbols-outlined text-[18px] {{ $isHighlighted ? 'text-white/80' : 'text-primary' }}">groups</span>
                        <span class="text-sm font-medium {{ $isHighlighted ? 'text-white/90' : 'text-slate-700' }}">
                            @if($plan['employee_limit'])
                                Up to {{ $plan['employee_limit'] }} employees
                            @else
                                Unlimited employees
                            @endif
                        </span>
                    </div>

                    {{-- Module list --}}
                    <ul class="space-y-2.5 flex-1 mb-8">
                        @foreach($allModules as $moduleKey => $moduleLabel)
                        @php $included = in_array($moduleKey, $plan['modules']); @endphp
                        <li class="flex items-center gap-2.5 text-sm">
                            @if($included)
                                <span class="material-symbols-outlined filled text-[18px] flex-shrink-0 {{ $isHighlighted ? 'text-white' : 'text-primary' }}">check_circle</span>
                                <span class="{{ $isHighlighted ? 'text-white/90' : 'text-slate-700' }}">{{ $moduleLabel }}</span>
                            @else
                                <span class="material-symbols-outlined text-[18px] flex-shrink-0 {{ $isHighlighted ? 'text-white/30' : 'text-slate-300' }}">cancel</span>
                                <span class="{{ $isHighlighted ? 'text-white/30' : 'text-slate-300' }}">{{ $moduleLabel }}</span>
                            @endif
                        </li>
                        @endforeach
                    </ul>

                    {{-- CTA --}}
                    <a href="{{ route('signup', ['plan' => $key, 'billing' => $billing]) }}"
                        class="block text-center py-3 rounded-2xl text-sm font-semibold transition-all duration-200
                        {{ $isHighlighted
                            ? 'bg-white text-primary hover:bg-slate-50'
                            : 'bg-primary text-white hover:bg-primary-dim' }}">
                        @if($plan['monthly_price'] === 0)
                            Get started free
                        @else
                            Start {{ $plan['name'] }}
                        @endif
                    </a>

                </div>
            </div>
            @endforeach

        </div>
    </section>

    {{-- ── Module comparison table ── --}}
    <section class="max-w-5xl mx-auto px-6 pb-24">
        <h2 class="font-headline font-bold text-2xl text-slate-900 text-center mb-8">Full feature comparison</h2>

        <div class="bg-white rounded-3xl border border-slate-200 overflow-hidden shadow-sm">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-slate-100">
                        <th class="text-left px-6 py-4 font-semibold text-slate-700 w-1/3">Module</th>
                        @foreach($this->plans as $plan)
                        <th class="px-4 py-4 font-semibold text-center
                            {{ $plan['highlighted'] ? 'text-primary' : 'text-slate-700' }}">
                            {{ $plan['name'] }}
                        </th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    {{-- Employee limit row --}}
                    <tr class="border-b border-slate-50 bg-slate-50/50">
                        <td class="px-6 py-3.5 font-medium text-slate-600">Max employees</td>
                        @foreach($this->plans as $plan)
                        <td class="px-4 py-3.5 text-center text-slate-700 font-medium">
                            {{ $plan['employee_limit'] ? $plan['employee_limit'] : '∞' }}
                        </td>
                        @endforeach
                    </tr>
                    {{-- Module rows --}}
                    @foreach($this->modules as $moduleKey => $moduleLabel)
                    <tr class="border-b border-slate-50 hover:bg-slate-50/50 transition-colors">
                        <td class="px-6 py-3.5 text-slate-600">{{ $moduleLabel }}</td>
                        @foreach($this->plans as $plan)
                        <td class="px-4 py-3.5 text-center">
                            @if(in_array($moduleKey, $plan['modules']))
                                <span class="material-symbols-outlined filled text-primary text-[20px]">check_circle</span>
                            @else
                                <span class="material-symbols-outlined text-slate-200 text-[20px]">remove</span>
                            @endif
                        </td>
                        @endforeach
                    </tr>
                    @endforeach
                    {{-- Price row --}}
                    <tr class="bg-slate-50/50">
                        <td class="px-6 py-4 font-semibold text-slate-700">Price</td>
                        @foreach($this->plans as $plan)
                        <td class="px-4 py-4 text-center">
                            <div class="font-headline font-bold text-slate-900">{{ $this->price($plan) }}</div>
                            @if($billing === 'yearly' && $plan['monthly_price'] > 0)
                            <div class="text-[11px] text-slate-400">${{ $plan['yearly_price'] }}/yr</div>
                            @endif
                        </td>
                        @endforeach
                    </tr>
                </tbody>
            </table>
        </div>
    </section>

    {{-- ── FAQ ── --}}
    <section class="max-w-2xl mx-auto px-6 pb-24" x-data="{ open: null }">
        <h2 class="font-headline font-bold text-2xl text-slate-900 text-center mb-8">Frequently asked questions</h2>

        @foreach([
            ['Can I switch plans later?', 'Yes — you can upgrade or downgrade at any time. Changes take effect immediately and billing is prorated.'],
            ['What happens when I hit the employee limit?', 'You\'ll be prompted to upgrade to the next plan. Existing data is never deleted.'],
            ['Is there a free trial for paid plans?', 'All paid plans come with a 14-day free trial. No credit card required to start.'],
            ['Can I pick individual modules?', 'Plans are bundled for simplicity, but Enterprise customers can contact us for custom module configurations.'],
            ['What payment methods do you accept?', 'We accept all major credit cards and EFT for annual plans.'],
        ] as $index => [$q, $a])
        <div class="border-b border-slate-100 py-4">
            <button
                class="w-full flex items-center justify-between text-left gap-4"
                @click="open = open === {{ $index }} ? null : {{ $index }}"
            >
                <span class="font-medium text-slate-800">{{ $q }}</span>
                <span class="material-symbols-outlined text-slate-400 text-[20px] flex-shrink-0 transition-transform duration-200"
                    :class="open === {{ $index }} ? 'rotate-180' : ''">expand_more</span>
            </button>
            <div x-show="open === {{ $index }}" class="pt-3 text-sm text-slate-500 leading-relaxed">
                {{ $a }}
            </div>
        </div>
        @endforeach
    </section>

</div>

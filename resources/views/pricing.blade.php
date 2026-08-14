@php
    $priceFor = function (array $plan) use ($billing): string {
        $amount = $billing === 'yearly' ? $plan['yearly_price'] : $plan['monthly_price'];

        if ($amount === 0) {
            return 'Free';
        }

        return '$' . number_format($billing === 'yearly' ? $amount / 12 : $amount, 0) . '/mo';
    };

    $yearlySavings = function (array $plan): ?string {
        if ($plan['monthly_price'] === 0) {
            return null;
        }

        $annualIfMonthly = $plan['monthly_price'] * 12;
        $savings = $annualIfMonthly - $plan['yearly_price'];

        return $savings > 0 ? 'Save $' . $savings . '/yr' : null;
    };
@endphp

<div>
    <section class="pt-20 pb-12 text-center px-6">
        <span class="inline-flex items-center gap-2 bg-primary/10 text-primary text-xs font-semibold px-3 py-1.5 rounded-full border border-primary/20 mb-6">
            <span class="w-1.5 h-1.5 rounded-full bg-primary"></span>
            Simple, transparent pricing
        </span>
        <h1 class="font-headline font-black text-5xl md:text-6xl text-slate-900 leading-tight tracking-tight mb-4">
            Pay for what<br/>your team <span class="text-primary italic">actually needs</span>
        </h1>
        <p class="text-slate-500 text-lg max-w-xl mx-auto mb-10">
            Start free, scale as you grow. Every plan includes core HR tools.
        </p>

        <div class="inline-flex items-center bg-slate-100 rounded-2xl p-1 gap-1">
            <a href="{{ route('pricing', ['billing' => 'monthly']) }}"
                class="px-5 py-2 rounded-xl text-sm font-medium transition-all duration-200 {{ $billing === 'monthly' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500 hover:text-slate-700' }}">
                Monthly
            </a>
            <a href="{{ route('pricing', ['billing' => 'yearly']) }}"
                class="px-5 py-2 rounded-xl text-sm font-medium transition-all duration-200 flex items-center gap-2 {{ $billing === 'yearly' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500 hover:text-slate-700' }}">
                Yearly
                <span class="bg-emerald-100 text-emerald-700 text-[10px] font-bold px-2 py-0.5 rounded-full">2 months free</span>
            </a>
        </div>
    </section>

    <section class="max-w-7xl mx-auto px-6 pb-16">
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-6">
            @foreach($plans as $key => $plan)
                @php
                    $isHighlighted = $plan['highlighted'];
                    $price = $priceFor($plan);
                    $savings = $yearlySavings($plan);
                @endphp

                <div class="relative flex flex-col rounded-3xl border transition-all duration-200 {{ $isHighlighted ? 'bg-primary border-primary shadow-2xl shadow-primary/20 text-white scale-[1.02]' : 'bg-white border-slate-200 hover:border-primary/40 hover:shadow-lg' }}">
                    @if($plan['badge'])
                        <div class="absolute -top-3.5 left-1/2 -translate-x-1/2">
                            <span class="bg-amber-400 text-amber-900 text-[11px] font-bold px-3 py-1 rounded-full shadow-sm whitespace-nowrap">
                                {{ $plan['badge'] }}
                            </span>
                        </div>
                    @endif

                    <div class="p-7 flex flex-col flex-1">
                        <div class="mb-6">
                            <h2 class="font-headline font-bold text-xl mb-1 {{ $isHighlighted ? 'text-white' : 'text-slate-900' }}">
                                {{ $plan['name'] }}
                            </h2>
                            <p class="text-sm {{ $isHighlighted ? 'text-white/70' : 'text-slate-500' }}">
                                {{ $plan['description'] }}
                            </p>
                        </div>

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

                        <div class="mb-5">
                            @if($savings && $billing === 'yearly')
                                <span class="inline-block bg-emerald-100 text-emerald-700 text-[11px] font-semibold px-2.5 py-1 rounded-full">
                                    {{ $savings }}
                                </span>
                            @endif
                        </div>

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

                        <ul class="space-y-2.5 flex-1 mb-8">
                            @foreach($modules as $moduleKey => $moduleLabel)
                                @php $included = in_array($moduleKey, $plan['modules'], true); @endphp
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

                        @if($key === 'free')
                            <a href="{{ route('register') }}"
                                class="block text-center py-3 rounded-2xl text-sm font-semibold transition-all duration-200 {{ $isHighlighted ? 'bg-white text-primary hover:bg-slate-50' : 'bg-primary text-white hover:bg-primary-dim' }}">
                                Get started free
                            </a>
                        @else
                            <button type="button" disabled class="block w-full text-center py-3 rounded-2xl text-sm font-semibold bg-slate-100 text-slate-400 cursor-not-allowed">
                                Coming soon
                            </button>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    </section>
</div>

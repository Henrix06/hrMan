@php
    $planCfg  = $this->planConfig;
    $isFree   = $this->isFree;
    $planName = $planCfg['name'] ?? 'Free';
    $modules  = config('pricing.modules', []);
    $eftRef   = 'HRMAN-' . strtoupper(\Illuminate\Support\Str::random(6));
@endphp

<x-slot name="sidebar">
    {{-- Plan badge --}}
    <div class="mb-8">
        <span class="inline-flex items-center gap-2 bg-primary/20 text-primary text-xs font-semibold px-3 py-1.5 rounded-full border border-primary/30 mb-4">
            <span class="w-1.5 h-1.5 rounded-full bg-primary"></span>
            Selected plan
        </span>
        <h2 class="font-headline font-black text-4xl text-white mb-1">{{ $planName }}</h2>
        <div class="font-headline font-bold text-2xl text-primary">{{ $this->planPrice }}</div>
        @if(! $isFree)
        <p class="text-slate-400 text-sm mt-1">
            Billed {{ $billing === 'yearly' ? 'annually' : 'monthly' }} · 14-day free trial
        </p>
        @else
        <p class="text-slate-400 text-sm mt-1">No credit card required</p>
        @endif
    </div>

    {{-- Included modules --}}
    <div class="space-y-2.5 mb-8">
        <p class="text-slate-500 text-xs font-semibold uppercase tracking-widest mb-3">Included modules</p>
        @foreach($modules as $key => $label)
            @php $included = in_array($key, $planCfg['modules'] ?? []); @endphp
            <div class="flex items-center gap-2.5 text-sm {{ $included ? 'text-white' : 'text-slate-600' }}">
                @if($included)
                    <span class="material-symbols-outlined filled text-primary text-[16px]">check_circle</span>
                @else
                    <span class="material-symbols-outlined text-slate-700 text-[16px]">remove</span>
                @endif
                {{ $label }}
            </div>
        @endforeach
    </div>

    {{-- Employee limit --}}
    <div class="bg-white/5 rounded-2xl p-4 border border-white/10">
        <div class="flex items-center gap-2 text-sm text-slate-300">
            <span class="material-symbols-outlined text-primary text-[18px]">groups</span>
            @if($planCfg['employee_limit'] ?? null)
                Up to {{ $planCfg['employee_limit'] }} employees
            @else
                Unlimited employees
            @endif
        </div>
    </div>

    {{-- Step indicator --}}
    <div class="mt-10 flex items-center gap-3">
        @foreach(['Account', 'Payment', 'Done'] as $i => $label)
            @php $num = $i + 1; @endphp
            <div class="flex items-center gap-2">
                <div class="w-6 h-6 rounded-full flex items-center justify-center text-xs font-bold
                    {{ $step >= $num ? 'bg-primary text-white' : 'bg-white/10 text-slate-500' }}">
                    @if($step > $num)
                        <span class="material-symbols-outlined filled text-[14px]">check</span>
                    @else
                        {{ $num }}
                    @endif
                </div>
                <span class="text-xs {{ $step >= $num ? 'text-white' : 'text-slate-600' }}">{{ $label }}</span>
            </div>
            @if($i < 2)
                <div class="flex-1 h-px {{ $step > $num ? 'bg-primary' : 'bg-white/10' }}"></div>
            @endif
        @endforeach
    </div>
</x-slot>

{{-- ════════════════════════════════════════════════════════════════════════════
     STEP 1 — Credentials
════════════════════════════════════════════════════════════════════════════ --}}
@if($step === 1)
<div class="space-y-7">
    <div>
        <h2 class="font-headline font-black text-3xl text-slate-900 mb-1">Create your account</h2>
        <p class="text-slate-400 text-sm">
            You'll be the super admin for your organisation's workspace.
        </p>
    </div>

    @if($error_message)
    <div class="px-4 py-3 rounded-xl bg-red-50 border border-red-200 text-red-700 text-sm">
        {{ $error_message }}
    </div>
    @endif

    <form wire:submit="nextToPayment" class="space-y-5">

        {{-- Personal info --}}
        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-widest mb-1.5">Full Name</label>
                <input wire:model="name" type="text" placeholder="Jane Doe" required autofocus
                    class="auth-input @error('name') error @enderror"/>
                @error('name') <p class="auth-error">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-widest mb-1.5">Work Email</label>
                <input wire:model="email" type="email" placeholder="jane@company.com" required
                    class="auth-input @error('email') error @enderror"/>
                @error('email') <p class="auth-error">{{ $message }}</p> @enderror
            </div>
        </div>

        {{-- Password --}}
        <div class="grid gap-4 sm:grid-cols-2" x-data="{ show: false }">
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-widest mb-1.5">Password</label>
                <div class="relative">
                    <input wire:model="password" :type="show ? 'text' : 'password'"
                        placeholder="Create a password" required
                        class="auth-input pr-12 @error('password') error @enderror"/>
                    <button type="button" @click="show = !show"
                        class="absolute right-3.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 transition-colors focus:outline-none">
                        <span class="material-symbols-outlined text-[20px]" x-text="show ? 'visibility_off' : 'visibility'"></span>
                    </button>
                </div>
                @error('password') <p class="auth-error">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-widest mb-1.5">Confirm Password</label>
                <div class="relative">
                    <input wire:model="password_confirmation" :type="show ? 'text' : 'password'"
                        placeholder="Repeat password" required
                        class="auth-input pr-12"/>
                    <button type="button" @click="show = !show"
                        class="absolute right-3.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 transition-colors focus:outline-none">
                        <span class="material-symbols-outlined text-[20px]" x-text="show ? 'visibility_off' : 'visibility'"></span>
                    </button>
                </div>
            </div>
        </div>

        {{-- Company --}}
        <div class="pt-2 border-t border-slate-100">
            <p class="text-xs font-semibold text-slate-500 uppercase tracking-widest mb-4">Your Company</p>

            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-widest mb-1.5">Company / Organisation Name</label>
                <input wire:model="company_name" type="text"
                    placeholder="Acme Corp" required
                    class="auth-input @error('company_name') error @enderror"/>
                @error('company_name') <p class="auth-error">{{ $message }}</p> @enderror
            </div>
        </div>

        <button type="submit" wire:loading.attr="disabled"
            class="w-full h-14 rounded-xl bg-gradient-to-br from-primary to-primary-container text-white font-headline font-bold text-base shadow-lg shadow-primary/20 hover:opacity-90 active:scale-[0.98] transition-all disabled:opacity-60 flex items-center justify-center gap-2">
            <span wire:loading.remove>
                @if($isFree)
                    Create Free Workspace
                @else
                    Continue to Payment
                @endif
            </span>
            <span wire:loading class="flex items-center gap-2">
                <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/>
                </svg>
                Setting up…
            </span>
        </button>
    </form>

    <p class="text-center text-slate-400 text-sm">
        Already have an account?
        <a href="{{ route('login') }}" class="text-primary font-semibold hover:underline">Sign in</a>
    </p>
</div>
@endif

{{-- ════════════════════════════════════════════════════════════════════════════
     STEP 2 — Payment
════════════════════════════════════════════════════════════════════════════ --}}
@if($step === 2)
<div class="space-y-7" x-data="{ method: @entangle('payment_method') }">
    <div>
        <button wire:click="backToCredentials" class="flex items-center gap-1 text-slate-400 hover:text-slate-700 text-sm mb-4 transition-colors">
            <span class="material-symbols-outlined text-[18px]">arrow_back</span>
            Back
        </button>
        <h2 class="font-headline font-black text-3xl text-slate-900 mb-1">Payment details</h2>
        <p class="text-slate-400 text-sm">
            Your 14-day free trial starts today. You won't be charged until the trial ends.
        </p>
    </div>

    {{-- Method toggle --}}
    <div class="flex gap-3">
        <button type="button" @click="method = 'card'" wire:click="$set('payment_method', 'card')"
            :class="method === 'card' ? 'border-primary bg-primary/5 text-primary' : 'border-slate-200 text-slate-500'"
            class="flex-1 flex items-center justify-center gap-2 h-12 rounded-xl border-2 text-sm font-semibold transition-all">
            <span class="material-symbols-outlined text-[18px]">credit_card</span>
            Credit / Debit Card
        </button>
        <button type="button" @click="method = 'eft'" wire:click="$set('payment_method', 'eft')"
            :class="method === 'eft' ? 'border-primary bg-primary/5 text-primary' : 'border-slate-200 text-slate-500'"
            class="flex-1 flex items-center justify-center gap-2 h-12 rounded-xl border-2 text-sm font-semibold transition-all">
            <span class="material-symbols-outlined text-[18px]">account_balance</span>
            EFT / Bank Transfer
        </button>
    </div>

    <form wire:submit="submitPayment" class="space-y-5">

        {{-- Card fields --}}
        <div x-show="method === 'card'" class="space-y-4">
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-widest mb-1.5">Cardholder Name</label>
                <input wire:model="card_name" type="text" placeholder="Jane Doe"
                    class="auth-input @error('card_name') error @enderror"/>
                @error('card_name') <p class="auth-error">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-widest mb-1.5">Card Number</label>
                <div class="relative">
                    <input wire:model="card_number" type="text" placeholder="1234 5678 9012 3456"
                        maxlength="19"
                        x-on:input="$el.value = $el.value.replace(/\D/g,'').replace(/(.{4})/g,'$1 ').trim()"
                        class="auth-input pr-12 @error('card_number') error @enderror"/>
                    <span class="absolute right-4 top-1/2 -translate-y-1/2 material-symbols-outlined text-slate-300 text-[20px]">credit_card</span>
                </div>
                @error('card_number') <p class="auth-error">{{ $message }}</p> @enderror
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-500 uppercase tracking-widest mb-1.5">Expiry (MM/YY)</label>
                    <input wire:model="card_expiry" type="text" placeholder="08/27" maxlength="5"
                        x-on:input="
                            let v = $el.value.replace(/\D/g,'');
                            if(v.length >= 2) v = v.slice(0,2) + '/' + v.slice(2,4);
                            $el.value = v;
                        "
                        class="auth-input @error('card_expiry') error @enderror"/>
                    @error('card_expiry') <p class="auth-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-500 uppercase tracking-widest mb-1.5">CVC</label>
                    <input wire:model="card_cvc" type="password" placeholder="•••" maxlength="4"
                        class="auth-input @error('card_cvc') error @enderror"/>
                    @error('card_cvc') <p class="auth-error">{{ $message }}</p> @enderror
                </div>
            </div>

            {{-- Security note --}}
            <div class="flex items-center gap-2 text-xs text-slate-400 bg-slate-50 rounded-xl px-4 py-3">
                <span class="material-symbols-outlined text-[16px] text-emerald-500">lock</span>
                Your card details are encrypted and never stored on our servers.
            </div>
        </div>

        {{-- EFT fields --}}
        <div x-show="method === 'eft'" class="space-y-4">
            <div class="bg-blue-50 border border-blue-200 rounded-xl p-4 text-sm text-blue-700">
                <p class="font-semibold mb-1">Bank Transfer Instructions</p>
                <p class="text-blue-600 text-xs leading-relaxed">
                    Transfer your subscription amount to the account below and enter your reference number.
                    Your workspace will be activated once payment is confirmed (1–2 business days).
                </p>
                <div class="mt-3 space-y-1 text-xs font-mono bg-white/60 rounded-lg p-3">
                    <div><span class="text-blue-500">Bank:</span> First National Bank</div>
                    <div><span class="text-blue-500">Account:</span> 62 1234 5678</div>
                    <div><span class="text-blue-500">Branch:</span> 250 655</div>
                    <div><span class="text-blue-500">Reference:</span> {{ $eftRef }}</div>
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-widest mb-1.5">Your Payment Reference</label>
                <input wire:model="eft_reference" type="text" placeholder="e.g. UMSIZI-ABC123"
                    class="auth-input @error('eft_reference') error @enderror"/>
                @error('eft_reference') <p class="auth-error">{{ $message }}</p> @enderror
            </div>
        </div>

        {{-- Order summary --}}
        <div class="bg-slate-50 rounded-2xl p-4 border border-slate-100">
            <div class="flex items-center justify-between text-sm mb-2">
                <span class="text-slate-600">{{ $planName }} plan ({{ ucfirst($billing) }})</span>
                <span class="font-semibold text-slate-900">{{ $this->planPrice }}</span>
            </div>
            <div class="flex items-center justify-between text-sm">
                <span class="text-slate-600">14-day free trial</span>
                <span class="text-emerald-600 font-semibold">-{{ $this->planPrice }}</span>
            </div>
            <div class="border-t border-slate-200 mt-3 pt-3 flex items-center justify-between text-sm font-bold">
                <span class="text-slate-900">Due today</span>
                <span class="text-slate-900">$0.00</span>
            </div>
        </div>

        <button type="submit" wire:loading.attr="disabled"
            class="w-full h-14 rounded-xl bg-gradient-to-br from-primary to-primary-container text-white font-headline font-bold text-base shadow-lg shadow-primary/20 hover:opacity-90 active:scale-[0.98] transition-all disabled:opacity-60 flex items-center justify-center gap-2">
            <span wire:loading.remove>Start Free Trial</span>
            <span wire:loading class="flex items-center gap-2">
                <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/>
                </svg>
                Creating workspace…
            </span>
        </button>

        <p class="text-center text-xs text-slate-400">
            By continuing you agree to our
            <a href="#" class="text-primary hover:underline">Terms of Service</a> and
            <a href="#" class="text-primary hover:underline">Privacy Policy</a>.
        </p>
    </form>
</div>
@endif

{{-- ════════════════════════════════════════════════════════════════════════════
     STEP 3 — Provisioning / Done
════════════════════════════════════════════════════════════════════════════ --}}
@if($step === 3)
<div class="text-center space-y-6">

    @if($provisioning)
    {{-- Spinner --}}
    <div class="flex flex-col items-center gap-6 py-12">
        <div class="relative w-20 h-20">
            <div class="absolute inset-0 rounded-full border-4 border-primary/20"></div>
            <div class="absolute inset-0 rounded-full border-4 border-primary border-t-transparent animate-spin"></div>
            <div class="absolute inset-0 flex items-center justify-center">
                <span class="material-symbols-outlined text-primary text-[28px]">rocket_launch</span>
            </div>
        </div>
        <div>
            <h2 class="font-headline font-black text-2xl text-slate-900 mb-2">Setting up your workspace…</h2>
            <p class="text-slate-400 text-sm">Creating your subdomain, configuring modules, and provisioning your account.</p>
        </div>
        <div class="w-full max-w-xs space-y-2 text-left">
            @foreach(['Creating tenant record', 'Provisioning super admin', 'Enabling plan modules', 'Finalising workspace'] as $i => $step_label)
            <div class="flex items-center gap-3 text-sm text-slate-500">
                <div class="w-5 h-5 rounded-full bg-primary/10 flex items-center justify-center">
                    <svg class="animate-spin h-3 w-3 text-primary" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/>
                    </svg>
                </div>
                {{ $step_label }}
            </div>
            @endforeach
        </div>
    </div>

    @elseif($done)
    {{-- Success --}}
    <div class="flex flex-col items-center gap-6 py-8">
        <div class="w-20 h-20 rounded-full bg-emerald-100 flex items-center justify-center">
            <span class="material-symbols-outlined filled text-emerald-500 text-[40px]">check_circle</span>
        </div>

        <div>
            <h2 class="font-headline font-black text-3xl text-slate-900 mb-2">You're all set!</h2>
            <p class="text-slate-500 text-sm max-w-sm mx-auto">
                Your <strong>{{ $planName }}</strong> workspace has been created. You're logged in as the super admin.
            </p>
        </div>

        {{-- What's next --}}
        <div class="w-full space-y-2 text-left">
            <p class="text-xs font-semibold text-slate-500 uppercase tracking-widest mb-3">What's next</p>
            @foreach([
                ['person_add', 'Invite your team members', 'Add employees and assign roles'],
                ['settings', 'Configure your modules', 'Enable or disable features for your plan'],
                ['mail', 'Verify your email', 'Check your inbox to confirm your address'],
            ] as [$icon, $title, $desc])
            <div class="flex items-start gap-3 bg-slate-50 rounded-xl p-3 border border-slate-100">
                <span class="material-symbols-outlined text-primary text-[20px] mt-0.5">{{ $icon }}</span>
                <div>
                    <p class="text-sm font-semibold text-slate-800">{{ $title }}</p>
                    <p class="text-xs text-slate-400">{{ $desc }}</p>
                </div>
            </div>
            @endforeach
        </div>

        <a href="{{ route('dashboard') }}"
            class="w-full h-14 rounded-xl bg-gradient-to-br from-primary to-primary-container text-white font-headline font-bold text-base shadow-lg shadow-primary/20 hover:opacity-90 transition-all flex items-center justify-center gap-2">
            <span class="material-symbols-outlined text-[20px]">dashboard</span>
            Go to Dashboard
        </a>
    </div>

    @else
    {{-- Error fallback (shouldn't normally show — error redirects to step 1) --}}
    <div class="py-12">
        <p class="text-slate-500">Something went wrong. <a href="{{ route('signup') }}" class="text-primary underline">Try again</a>.</p>
    </div>
    @endif

</div>
@endif

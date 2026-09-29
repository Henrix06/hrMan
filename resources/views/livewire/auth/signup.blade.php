@php
    $planCfg  = $this->planConfig;
    $planName = $this->planName;
    $modules  = config('pricing.modules', []);
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
        <p class="text-slate-400 text-sm mt-1">14-day free trial · no credit card required</p>
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
</x-slot>

<div class="space-y-7">
    <div>
        <h2 class="font-headline font-black text-3xl text-slate-900 mb-1">Create your workspace</h2>
        <p class="text-slate-400 text-sm">
            You'll be the super admin for your organisation. No card needed to get started.
        </p>
    </div>

    @if($error_message)
    <div class="px-4 py-3 rounded-xl bg-red-50 border border-red-200 text-red-700 text-sm">
        {{ $error_message }}
    </div>
    @endif

    <form wire:submit="signup" class="space-y-5">

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
                        placeholder="Repeat password" required class="auth-input"/>
                </div>
            </div>
        </div>

        {{-- Company --}}
        <div class="pt-2 border-t border-slate-100">
            <p class="text-xs font-semibold text-slate-500 uppercase tracking-widest mb-4">Your Company</p>

            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-widest mb-1.5">Company / Organisation Name</label>
                <input wire:model="company_name" type="text" placeholder="Acme Corp" required
                    class="auth-input @error('company_name') error @enderror"/>
                @error('company_name') <p class="auth-error">{{ $message }}</p> @enderror
            </div>
        </div>

        <button type="submit" wire:loading.attr="disabled"
            class="w-full h-14 rounded-xl bg-gradient-to-br from-primary to-primary-container text-white font-headline font-bold text-base shadow-lg shadow-primary/20 hover:opacity-90 active:scale-[0.98] transition-all disabled:opacity-60 flex items-center justify-center gap-2">
            <span wire:loading.remove>Create Workspace</span>
            <span wire:loading class="flex items-center gap-2">
                <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/>
                </svg>
                Setting up your workspace…
            </span>
        </button>

        <p class="text-center text-xs text-slate-400">
            By continuing you agree to our
            <a href="#" class="text-primary hover:underline">Terms of Service</a> and
            <a href="#" class="text-primary hover:underline">Privacy Policy</a>.
        </p>
    </form>

    <p class="text-center text-slate-400 text-sm">
        Already have an account?
        <a href="{{ route('login') }}" class="text-primary font-semibold hover:underline">Sign in</a>
    </p>
</div>

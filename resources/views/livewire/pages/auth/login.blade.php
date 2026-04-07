<?php

use App\Livewire\Forms\LoginForm;
use Illuminate\Support\Facades\Session;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component
{
    public LoginForm $form;

    public function login(): void
    {
        $this->validate();
        $this->form->authenticate();
        Session::regenerate();
        $this->redirectIntended(default: route('dashboard', absolute: false), navigate: true);
    }
}; ?>

<div class="space-y-8">
    <div class="space-y-2">
        <h2 class="font-headline text-3xl font-bold text-on-surface">Welcome back</h2>
        <p class="text-on-surface-variant font-body text-sm">Please enter your details to sign in.</p>
    </div>

    @if (session('status'))
        <div class="px-4 py-3 rounded-xl bg-green-50 border border-green-200 text-green-700 text-sm">
            {{ session('status') }}
        </div>
    @endif

    <form wire:submit="login" class="space-y-5">
        <div class="space-y-2">
            <label for="email" class="block font-label text-sm font-semibold text-on-surface ml-1">Email Address</label>
            <input wire:model="form.email" id="email" type="email" name="email"
                placeholder="name@company.com" required autofocus autocomplete="username"
                class="auth-input"/>
            <x-input-error :messages="$errors->get('form.email')" class="auth-error"/>
        </div>

        <div class="space-y-2">
            <div class="flex justify-between items-center ml-1">
                <label for="password" class="block font-label text-sm font-semibold text-on-surface">Password</label>
                @if (Route::has('password.request'))
                    <a href="{{ route('password.request') }}" wire:navigate
                        class="text-xs font-semibold text-primary hover:text-primary-dim transition-colors">
                        Forgot Password?
                    </a>
                @endif
            </div>
            <input wire:model="form.password" id="password" type="password" name="password"
                placeholder="••••••••" required autocomplete="current-password"
                class="auth-input"/>
            <x-input-error :messages="$errors->get('form.password')" class="auth-error"/>
        </div>

        <div class="flex items-center gap-2 ml-1">
            <input wire:model="form.remember" id="remember" type="checkbox" name="remember"
                class="h-4 w-4 rounded border-slate-300 text-primary focus:ring-primary"/>
            <label for="remember" class="text-sm text-on-surface-variant">Keep me signed in</label>
        </div>

        <button type="submit" wire:loading.attr="disabled"
            class="w-full h-14 rounded-xl bg-gradient-to-br from-primary to-primary-container text-on-primary font-headline font-bold text-lg shadow-lg shadow-primary/20 hover:opacity-90 active:scale-[0.98] transition-all disabled:opacity-60">
            Sign In
        </button>
    </form>

    <div class="relative flex items-center py-2">
        <div class="flex-grow border-t border-surface-variant"></div>
        <span class="flex-shrink mx-4 text-outline-variant text-xs font-semibold uppercase tracking-widest">Or</span>
        <div class="flex-grow border-t border-surface-variant"></div>
    </div>

    @if (Route::has('register'))
        <p class="text-center text-on-surface-variant font-body text-sm">
            Don't have an account?
            <a href="{{ route('register') }}" wire:navigate class="text-primary font-bold hover:underline">Create an account</a>
        </p>
    @endif

    @php
        $demoAccounts = collect(config('demo_accounts', []));
    @endphp
    @if ($demoAccounts->isNotEmpty())
        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-5 space-y-4">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <h3 class="font-headline text-base font-bold text-slate-900">Demo Accounts</h3>
                    <p class="text-xs text-slate-500 mt-0.5">Use any of these to explore the portal.</p>
                </div>
                <span class="rounded-full bg-white border border-slate-200 px-3 py-1 text-[10px] font-bold uppercase tracking-widest text-primary">Seeded</span>
            </div>
            <div class="space-y-2">
                @foreach ($demoAccounts as $account)
                    <div class="rounded-xl border border-slate-200 bg-white px-4 py-3 shadow-sm flex flex-col sm:flex-row sm:items-center sm:justify-between gap-1">
                        <div>
                            <div class="text-sm font-bold text-slate-900">{{ $account['role'] }}</div>
                            <div class="text-xs text-slate-500">{{ $account['email'] }}</div>
                        </div>
                        <div class="text-xs text-slate-500">
                            pw: <span class="font-bold text-slate-800">{{ $account['password'] }}</span>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</div>

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

<div class="space-y-7">
    <div>
        @if(isset($currentTenant))
        <div class="flex items-center gap-2 mb-4">
            <div class="w-8 h-8 rounded-lg bg-primary/10 flex items-center justify-center">
                <span class="material-symbols-outlined text-primary text-[16px]">business</span>
            </div>
            <div>
                <p class="text-xs text-slate-400 leading-none">Signing in to</p>
                <p class="text-sm font-semibold text-slate-800 leading-tight">{{ $currentTenant->name }}</p>
            </div>
        </div>
        @endif
        <h2 class="font-headline font-black text-3xl text-slate-900 mb-1.5">Welcome back</h2>
        <p class="text-slate-400 text-sm">
            @isset($currentTenant)
                Sign in to your {{ $currentTenant->name }} workspace.
            @else
                Sign in to your uMsizi account.
            @endisset
        </p>
    </div>

    @if (session('status'))
    <div class="px-4 py-3 rounded-xl bg-green-50 border border-green-200 text-green-700 text-sm">
        {{ session('status') }}
    </div>
    @endif

    <form wire:submit="login" class="space-y-4">
        <div>
            <label for="email" class="block text-xs font-semibold text-slate-500 uppercase tracking-widest mb-1.5">Email Address</label>
            <input wire:model="form.email" id="email" type="email" name="email"
                placeholder="name@company.com" required autofocus autocomplete="username"
                class="auth-input"/>
            <x-input-error :messages="$errors->get('form.email')" class="auth-error"/>
        </div>

        <div>
            <div class="flex items-center justify-between mb-1.5">
                <label for="password" class="block text-xs font-semibold text-slate-500 uppercase tracking-widest">Password</label>
                @if (Route::has('password.request'))
                <a href="{{ route('password.request') }}" wire:navigate
                    class="text-xs font-semibold text-primary hover:text-primary-dim transition-colors">
                    Forgot password?
                </a>
                @endif
            </div>
            <div class="relative" x-data="{ show: false }">
                <input wire:model="form.password" id="password" name="password"
                    :type="show ? 'text' : 'password'"
                    placeholder="••••••••" required autocomplete="current-password"
                    class="auth-input pr-12"/>
                <button type="button" @click="show = !show"
                    class="absolute right-3.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 transition-colors focus:outline-none"
                    :aria-label="show ? 'Hide password' : 'Show password'">
                    <span class="material-symbols-outlined text-[20px]" x-text="show ? 'visibility_off' : 'visibility'"></span>
                </button>
            </div>
            <x-input-error :messages="$errors->get('form.password')" class="auth-error"/>
        </div>

        <div class="flex items-center gap-2.5 pt-1">
            <input wire:model="form.remember" id="remember" type="checkbox" name="remember"
                class="w-4 h-4 rounded border-slate-300 text-primary focus:ring-primary focus:ring-offset-0"/>
            <label for="remember" class="text-sm text-slate-500 select-none">Keep me signed in</label>
        </div>

        <button type="submit" wire:loading.attr="disabled"
            class="w-full h-13 py-3.5 rounded-xl bg-primary text-white font-headline font-bold text-base shadow-md shadow-primary/25 hover:bg-primary-dim active:scale-[0.98] transition-all disabled:opacity-60 flex items-center justify-center gap-2">
            <span wire:loading.remove>Sign In</span>
            <span wire:loading class="flex items-center gap-2">
                <svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path>
                </svg>
                Signing in…
            </span>
        </button>
    </form>

    @php $demoAccounts = collect(config('demo_accounts.accounts', [])); @endphp
    @if ($demoAccounts->isNotEmpty() && ! isset($currentTenant))
    <div class="rounded-2xl border border-slate-100 bg-slate-50 p-5 space-y-3">
        <div class="flex items-center justify-between">
            <div>
                <h3 class="font-headline font-bold text-sm text-slate-900">Demo Accounts</h3>
                <p class="text-[11px] text-slate-400 mt-0.5">Click any to auto-fill credentials.</p>
            </div>
            <span class="rounded-full bg-primary/10 text-primary px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-widest">Seeded</span>
        </div>
        <div class="space-y-2">
            @foreach ($demoAccounts as $account)
            <button type="button"
                wire:click="$set('form.email', '{{ $account['email'] }}')"
                class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3 shadow-sm flex items-center justify-between gap-2 hover:border-primary/30 hover:bg-primary/5 transition-colors text-left group">
                <div>
                    <div class="text-xs font-bold text-slate-900 group-hover:text-primary transition-colors">{{ $account['role'] }}</div>
                    <div class="text-[11px] text-slate-400">{{ $account['email'] }}</div>
                </div>
                <div class="text-[11px] text-slate-400 flex-shrink-0">
                    pw: <span class="font-bold text-slate-700">{{ $account['password'] }}</span>
                </div>
            </button>
            @endforeach
        </div>
    </div>
    @endif
</div>

<?php

use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component
{
    public string $name = '';
    public string $email = '';
    public string $password = '';
    public string $password_confirmation = '';

    public function register(): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'string', 'confirmed', Rules\Password::defaults()],
        ]);

        $validated['password'] = Hash::make($validated['password']);

        event(new Registered($user = User::create($validated)));

        Auth::login($user);

        $this->redirect(route('dashboard', absolute: false), navigate: true);
    }
}; ?>

<div class="space-y-8">
    <div class="space-y-2">
        <h2 class="font-headline text-3xl font-bold text-on-surface">Create your account</h2>
        <p class="text-on-surface-variant font-body text-sm">
            Bring employee data, leave management, and payroll into one home.
        </p>
    </div>

    <form wire:submit="register" class="space-y-5">
        <div class="space-y-2">
            <label for="name" class="block font-label text-sm font-semibold text-on-surface ml-1">Full Name</label>
            <input wire:model="name" id="name" type="text" name="name"
                placeholder="Jane Doe" required autofocus autocomplete="name"
                class="auth-input"/>
            <x-input-error :messages="$errors->get('name')" class="auth-error"/>
        </div>

        <div class="space-y-2">
            <label for="email" class="block font-label text-sm font-semibold text-on-surface ml-1">Work Email</label>
            <input wire:model="email" id="email" type="email" name="email"
                placeholder="jane@company.com" required autocomplete="username"
                class="auth-input"/>
            <x-input-error :messages="$errors->get('email')" class="auth-error"/>
        </div>

        <div class="grid gap-5 sm:grid-cols-2">
            <div class="space-y-2">
                <label for="password" class="block font-label text-sm font-semibold text-on-surface ml-1">Password</label>
                <input wire:model="password" id="password" type="password" name="password"
                    placeholder="Create a password" required autocomplete="new-password"
                    class="auth-input"/>
                <x-input-error :messages="$errors->get('password')" class="auth-error"/>
            </div>
            <div class="space-y-2">
                <label for="password_confirmation" class="block font-label text-sm font-semibold text-on-surface ml-1">Confirm Password</label>
                <input wire:model="password_confirmation" id="password_confirmation" type="password"
                    name="password_confirmation" placeholder="Repeat your password" required autocomplete="new-password"
                    class="auth-input"/>
                <x-input-error :messages="$errors->get('password_confirmation')" class="auth-error"/>
            </div>
        </div>

        <button type="submit" wire:loading.attr="disabled"
            class="w-full h-14 rounded-xl bg-gradient-to-br from-primary to-primary-container text-on-primary font-headline font-bold text-lg shadow-lg shadow-primary/20 hover:opacity-90 active:scale-[0.98] transition-all disabled:opacity-60">
            Create Account
        </button>
    </form>

    <p class="text-center text-on-surface-variant font-body text-sm">
        Already have an account?
        <a href="{{ route('login') }}" wire:navigate class="text-primary font-bold hover:underline">Sign in instead</a>
    </p>
</div>

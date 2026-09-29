<div class="space-y-7">

    @if($error)
    <div>
        <div class="w-14 h-14 rounded-2xl bg-red-50 flex items-center justify-center mb-6">
            <span class="material-symbols-outlined text-red-500 text-[28px]">link_off</span>
        </div>
        <h2 class="font-headline font-black text-3xl text-slate-900 mb-1.5">Invitation unavailable</h2>
        <p class="text-slate-500 text-sm">{{ $error }}</p>

        <div class="mt-8 flex flex-col gap-3">
            <a href="{{ route('login') }}"
                class="w-full text-center py-3.5 rounded-xl bg-primary text-white font-headline font-bold text-sm shadow-md shadow-primary/25 hover:bg-primary-dim transition-colors">
                Go to sign in
            </a>
            <a href="/" class="w-full text-center py-3 rounded-xl border border-slate-200 text-sm font-semibold text-slate-600 hover:border-slate-300 transition-colors">
                Back to home
            </a>
        </div>
    </div>
    @else
    <div>
        <h2 class="font-headline font-black text-3xl text-slate-900 mb-1.5">
            Join {{ $tenantName ?? 'the workspace' }}
        </h2>
        <p class="text-slate-400 text-sm">
            You've been invited{{ $role ? ' as ' . $role : '' }}. Set your details to activate your account.
        </p>
    </div>

    <div class="flex items-center gap-3 px-4 py-3 rounded-xl bg-slate-50 border border-slate-100">
        <span class="material-symbols-outlined text-primary text-[20px]">mail</span>
        <div class="min-w-0">
            <p class="text-[10px] font-semibold text-slate-400 uppercase tracking-widest">Invited email</p>
            <p class="text-sm font-semibold text-slate-700 truncate">{{ $email }}</p>
        </div>
        @if($role)
        <span class="ml-auto text-[10px] font-bold uppercase tracking-widest px-2.5 py-1 rounded-full bg-primary/10 text-primary">{{ $role }}</span>
        @endif
    </div>

    <form wire:submit="accept" class="space-y-4">
        <div>
            <label for="name" class="block text-xs font-semibold text-slate-500 uppercase tracking-widest mb-1.5">Full name</label>
            <input wire:model="name" id="name" type="text" placeholder="Jane Doe" required autofocus autocomplete="name"
                class="auth-input @error('name') error @enderror"/>
            @error('name') <p class="auth-error">{{ $message }}</p> @enderror
        </div>

        <div x-data="{ show: false }">
            <label for="password" class="block text-xs font-semibold text-slate-500 uppercase tracking-widest mb-1.5">Password</label>
            <div class="relative">
                <input wire:model="password" id="password" :type="show ? 'text' : 'password'"
                    placeholder="Create a password" required autocomplete="new-password"
                    class="auth-input pr-12 @error('password') error @enderror"/>
                <button type="button" @click="show = !show"
                    class="absolute right-3.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 transition-colors focus:outline-none"
                    :aria-label="show ? 'Hide password' : 'Show password'">
                    <span class="material-symbols-outlined text-[20px]" x-text="show ? 'visibility_off' : 'visibility'"></span>
                </button>
            </div>
            @error('password') <p class="auth-error">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="password_confirmation" class="block text-xs font-semibold text-slate-500 uppercase tracking-widest mb-1.5">Confirm password</label>
            <input wire:model="password_confirmation" id="password_confirmation" type="password"
                placeholder="Repeat password" required autocomplete="new-password"
                class="auth-input @error('password_confirmation') error @enderror"/>
        </div>

        <button type="submit" wire:loading.attr="disabled"
            class="w-full h-14 rounded-xl bg-primary text-white font-headline font-bold text-base shadow-md shadow-primary/25 hover:bg-primary-dim active:scale-[0.98] transition-all disabled:opacity-60 flex items-center justify-center gap-2">
            <span wire:loading.remove>Accept invitation</span>
            <span wire:loading class="flex items-center gap-2">
                <svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path>
                </svg>
                Creating your account…
            </span>
        </button>
    </form>

    <p class="text-center text-slate-400 text-sm">
        Already have an account?
        <a href="{{ route('login') }}" class="text-primary font-semibold hover:underline">Sign in</a>
    </p>
    @endif
</div>

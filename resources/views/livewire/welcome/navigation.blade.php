<nav class="flex flex-1 flex-wrap items-center justify-end gap-3">
    @auth
        <a
            href="{{ url('/dashboard') }}"
            wire:navigate
            class="inline-flex items-center justify-center rounded-2xl bg-[linear-gradient(135deg,#c96a44,#a84d2a)] px-5 py-3 text-sm font-semibold text-white shadow-[0_18px_34px_-20px_rgba(168,77,42,0.8)] transition hover:-translate-y-0.5"
        >
            Dashboard
        </a>
    @else
        <a
            href="{{ route('login') }}"
            wire:navigate
            class="inline-flex items-center justify-center rounded-2xl border border-slate-200 bg-white/80 px-5 py-3 text-sm font-semibold text-slate-900 transition hover:-translate-y-0.5 hover:border-slate-300"
        >
            Log in
        </a>

        @if (Route::has('register'))
            <a
                href="{{ route('register') }}"
                wire:navigate
                class="inline-flex items-center justify-center rounded-2xl bg-[#132238] px-5 py-3 text-sm font-semibold text-white transition hover:-translate-y-0.5"
            >
                Register
            </a>
        @endif
    @endauth
</nav>

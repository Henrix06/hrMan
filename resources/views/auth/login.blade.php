<x-guest-layout>
    <div class="space-y-7">
        <div>
            <h2 class="font-headline font-black text-3xl text-slate-900 mb-1.5">Welcome back</h2>
            <p class="text-slate-400 text-sm">Sign in to your uMsizi account.</p>
        </div>

        <x-auth-session-status class="mb-4" :status="session('status')" />

        <form method="POST" action="{{ route('login') }}" class="space-y-4">
            @csrf

            <div>
                <label for="email" class="block text-xs font-semibold text-slate-500 uppercase tracking-widest mb-1.5">Email Address</label>
                <input id="email" type="email" name="email" value="{{ old('email') }}"
                    placeholder="name@company.com" required autofocus autocomplete="username"
                    class="auth-input"/>
                <x-input-error :messages="$errors->get('email')" class="auth-error"/>
            </div>

            <div>
                <div class="flex items-center justify-between mb-1.5">
                    <label for="password" class="block text-xs font-semibold text-slate-500 uppercase tracking-widest">Password</label>
                    @if (Route::has('password.request'))
                        <a href="{{ route('password.request') }}" class="text-xs font-semibold text-primary hover:text-primary-dim transition-colors">
                            Forgot password?
                        </a>
                    @endif
                </div>
                <input id="password" type="password" name="password"
                    placeholder="••••••••" required autocomplete="current-password"
                    class="auth-input"/>
                <x-input-error :messages="$errors->get('password')" class="auth-error"/>
            </div>

            <div class="flex items-center gap-2.5 pt-1">
                <input id="remember" type="checkbox" name="remember"
                    class="w-4 h-4 rounded border-slate-300 text-primary focus:ring-primary focus:ring-offset-0"/>
                <label for="remember" class="text-sm text-slate-500 select-none">Keep me signed in</label>
            </div>

            <button type="submit"
                class="w-full h-13 py-3.5 rounded-xl bg-primary text-white font-headline font-bold text-base shadow-md shadow-primary/25 hover:bg-primary-dim active:scale-[0.98] transition-all">
                Sign In
            </button>
        </form>

        @if ($demoAccounts->isNotEmpty())
            <div class="rounded-2xl border border-slate-100 bg-slate-50 p-5 space-y-3">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="font-headline font-bold text-sm text-slate-900">Demo Accounts</h3>
                        <p class="text-[11px] text-slate-400 mt-0.5">Seed data credentials.</p>
                    </div>
                    <span class="rounded-full bg-primary/10 text-primary px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-widest">Seeded</span>
                </div>
                <div class="space-y-2">
                    @foreach ($demoAccounts as $account)
                        <div class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3 shadow-sm flex items-center justify-between gap-2">
                            <div>
                                <div class="text-xs font-bold text-slate-900">{{ $account['role'] }}</div>
                                <div class="text-[11px] text-slate-400">{{ $account['email'] }}</div>
                            </div>
                            <div class="text-[11px] text-slate-400 flex-shrink-0">
                                pw: <span class="font-bold text-slate-700">{{ $account['password'] }}</span>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
</x-guest-layout>

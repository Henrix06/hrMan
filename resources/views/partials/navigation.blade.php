<nav class="border-b border-slate-200 bg-white">
    <div class="mx-auto flex max-w-7xl items-center justify-between px-6 py-4">
        <div class="flex items-center gap-6">
            <a href="{{ route('dashboard') }}" class="font-headline text-xl font-black text-slate-900">
                uMsizi<span class="text-primary">.</span>
            </a>
            <div class="hidden md:flex items-center gap-4 text-sm text-slate-600">
                <a href="{{ route('dashboard') }}" class="{{ request()->routeIs('dashboard') ? 'text-primary font-semibold' : 'hover:text-primary' }}">Dashboard</a>
                <a href="{{ route('employees.index') }}" class="{{ request()->routeIs('employees.*') ? 'text-primary font-semibold' : 'hover:text-primary' }}">Employees</a>
                <a href="{{ route('tasks.index') }}" class="{{ request()->routeIs('tasks.*') ? 'text-primary font-semibold' : 'hover:text-primary' }}">Tasks</a>
                <a href="{{ route('payroll.history') }}" class="{{ request()->routeIs('payroll.*', 'payslips.*') ? 'text-primary font-semibold' : 'hover:text-primary' }}">Payroll</a>
                <a href="{{ route('profile') }}" class="{{ request()->routeIs('profile') ? 'text-primary font-semibold' : 'hover:text-primary' }}">Profile</a>
            </div>
        </div>

        <div class="flex items-center gap-4">
            <div class="text-right">
                <div class="text-sm font-semibold text-slate-900">{{ auth()->user()->name }}</div>
                <div class="text-xs text-slate-500">{{ auth()->user()->email }}</div>
            </div>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-700 hover:border-slate-300 hover:text-slate-900">
                    Log out
                </button>
            </form>
        </div>
    </div>
</nav>

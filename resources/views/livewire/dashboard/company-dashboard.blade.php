<div>
    @if (session()->has('message'))
        <div class="mb-6 px-4 py-3 rounded-xl bg-green-50 border border-green-200 text-green-700 text-sm flex items-center gap-2">
            <span class="material-symbols-outlined text-sm">check_circle</span>
            {{ session('message') }}
        </div>
    @endif

    <!-- Welcome -->
    <section class="mb-8">
        <div class="flex flex-col md:flex-row md:items-end justify-between gap-4">
            <div>
                <h2 class="text-2xl md:text-4xl font-extrabold text-slate-900 font-headline tracking-tight">
                    Good {{ now()->hour < 12 ? 'morning' : (now()->hour < 17 ? 'afternoon' : 'evening') }}, {{ explode(' ', $user->name)[0] }}.
                </h2>
                <p class="text-slate-500 font-body mt-2">Here's your company at a glance.</p>
            </div>
            @can('create employee')
            <a href="{{ route('employees.create') }}" wire:navigate
                class="bg-gradient-to-br from-primary to-primary-container text-on-primary px-6 py-3 rounded-xl font-bold font-headline shadow-lg shadow-primary/20 hover:opacity-90 transition-opacity flex items-center justify-center gap-2 w-full md:w-fit">
                <span class="material-symbols-outlined text-sm">add</span> Add Employee
            </a>
            @endcan
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-8">
            <!-- Total Employees -->
            <a href="{{ route('employees.index') }}" wire:navigate
                class="glass-card p-5 rounded-[2rem] shadow-sm relative overflow-hidden group hover:shadow-md transition-shadow">
                <div class="absolute -right-3 -top-3 w-20 h-20 bg-primary/5 rounded-full group-hover:scale-150 transition-transform duration-700"></div>
                <div class="w-9 h-9 rounded-xl bg-primary/10 flex items-center justify-center text-primary mb-3">
                    <span class="material-symbols-outlined text-sm">groups</span>
                </div>
                <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1">Employees</p>
                <h3 class="text-4xl font-extrabold font-headline text-slate-900">{{ $totalEmployees }}</h3>
                <p class="text-[10px] text-slate-400 mt-1">{{ $activeEmployees }} active</p>
            </a>

            <!-- Departments -->
            <a href="{{ route('departments.index') }}" wire:navigate
                class="glass-card p-5 rounded-[2rem] shadow-sm relative overflow-hidden group hover:shadow-md transition-shadow">
                <div class="absolute -right-3 -top-3 w-20 h-20 bg-primary/5 rounded-full group-hover:scale-150 transition-transform duration-700"></div>
                <div class="w-9 h-9 rounded-xl bg-primary/10 flex items-center justify-center text-primary mb-3">
                    <span class="material-symbols-outlined text-sm">domain</span>
                </div>
                <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1">Departments</p>
                <h3 class="text-4xl font-extrabold font-headline text-slate-900">{{ $departments->count() }}</h3>
            </a>

            <!-- Pending Leave -->
            <a href="{{ route('leave.approval') }}" wire:navigate
                class="glass-card p-5 rounded-[2rem] shadow-sm relative overflow-hidden group hover:shadow-md transition-shadow">
                <div class="absolute -right-3 -top-3 w-20 h-20 bg-amber-500/5 rounded-full group-hover:scale-150 transition-transform duration-700"></div>
                <div class="w-9 h-9 rounded-xl bg-amber-100 flex items-center justify-center text-amber-600 mb-3">
                    <span class="material-symbols-outlined text-sm">event_busy</span>
                </div>
                <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1">Pending Leave</p>
                <h3 class="text-4xl font-extrabold font-headline text-slate-900">{{ $pendingLeaves }}</h3>
            </a>

            <!-- Pending Payroll -->
            <a href="{{ route('payroll.run') }}" wire:navigate
                class="glass-card p-5 rounded-[2rem] shadow-sm relative overflow-hidden group hover:shadow-md transition-shadow">
                <div class="absolute -right-3 -top-3 w-20 h-20 bg-emerald-500/5 rounded-full group-hover:scale-150 transition-transform duration-700"></div>
                <div class="w-9 h-9 rounded-xl bg-emerald-100 flex items-center justify-center text-emerald-600 mb-3">
                    <span class="material-symbols-outlined text-sm">payments</span>
                </div>
                <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1">Pending Payroll</p>
                <h3 class="text-4xl font-extrabold font-headline text-slate-900">{{ $pendingPayroll }}</h3>
            </a>
        </div>
    </section>

    <!-- Announcements banner -->
    @if($announcements->count() > 0)
    <section class="mb-8">
        <div class="glass-card p-5 rounded-[2rem]">
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-base font-bold font-headline text-slate-900">Announcements</h3>
                <a href="{{ route('announcements.index') }}" wire:navigate class="text-sm font-bold text-primary hover:bg-primary/5 px-3 py-1 rounded-xl transition-colors">View all</a>
            </div>
            <div class="space-y-2">
                @foreach($announcements as $ann)
                <div class="flex items-start gap-3 p-3 rounded-xl bg-white/60 {{ $ann->pinned ? 'border-l-4 border-primary' : '' }}">
                    <span class="material-symbols-outlined text-primary text-sm mt-0.5">campaign</span>
                    <div class="min-w-0">
                        <p class="text-sm font-bold text-slate-800">{{ $ann->title }}</p>
                        <p class="text-xs text-slate-500 mt-0.5 line-clamp-1">{{ $ann->body }}</p>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </section>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

        <!-- Department Breakdown (main panel) -->
        <div class="lg:col-span-2 glass-card p-8 rounded-[2.5rem]">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h3 class="text-2xl font-bold font-headline text-slate-900">Departments</h3>
                    <p class="text-slate-500 text-sm">Team distribution across departments</p>
                </div>
                <a href="{{ route('departments.index') }}" wire:navigate
                    class="text-sm font-bold text-primary hover:bg-primary/5 px-4 py-2 rounded-xl transition-colors">
                    View all
                </a>
            </div>

            @if($departments->count() > 0)
            <div class="space-y-0">
                @foreach($departments as $dept)
                <div class="flex items-center gap-4 py-4 border-b border-slate-100 last:border-0 hover:bg-slate-50/50 px-3 -mx-3 rounded-xl transition-colors">
                    <div class="w-9 h-9 rounded-xl bg-primary/10 flex items-center justify-center flex-shrink-0">
                        <span class="material-symbols-outlined text-sm text-primary">domain</span>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-bold text-slate-800 truncate">{{ $dept->name }}</p>
                        @if($dept->manager)
                        <p class="text-xs text-slate-400">Managed by {{ $dept->manager->name }}</p>
                        @endif
                    </div>
                    <div class="text-right">
                        <span class="text-sm font-extrabold text-slate-900">{{ $dept->employees_count }}</span>
                        <p class="text-[10px] text-slate-400">members</p>
                    </div>
                </div>
                @endforeach
            </div>
            @else
            <div class="flex flex-col items-center justify-center py-14 text-slate-400">
                <span class="material-symbols-outlined text-5xl mb-3">domain</span>
                <p class="text-sm">No departments yet.</p>
                <a href="{{ route('departments.index') }}" wire:navigate class="mt-3 text-sm font-bold text-primary hover:underline">Create one</a>
            </div>
            @endif
        </div>

        <!-- Right Column -->
        <div class="space-y-6">

            <!-- Company Info -->
            <div class="glass-card p-6 rounded-[2rem]">
                <h3 class="text-base font-bold font-headline text-slate-900 mb-4">Company</h3>
                <div class="space-y-3">
                    @if($tenant)
                    <div class="flex items-center justify-between p-3 rounded-xl bg-primary/5">
                        <div class="flex items-center gap-2">
                            <span class="material-symbols-outlined text-primary text-sm">business</span>
                            <span class="text-sm font-semibold text-slate-700">{{ $tenant->name }}</span>
                        </div>
                    </div>
                    <div class="flex items-center justify-between p-3 rounded-xl bg-slate-50">
                        <div class="flex items-center gap-2">
                            <span class="material-symbols-outlined text-slate-500 text-sm">star</span>
                            <span class="text-sm font-semibold text-slate-700">Plan</span>
                        </div>
                        <span class="text-sm font-extrabold text-slate-700">{{ $planConfig['name'] ?? 'Free' }}</span>
                    </div>
                    @if($isOnTrial)
                    <div class="flex items-center justify-between p-3 rounded-xl bg-amber-50">
                        <div class="flex items-center gap-2">
                            <span class="material-symbols-outlined text-amber-600 text-sm">timer</span>
                            <span class="text-sm font-semibold text-slate-700">Trial ends</span>
                        </div>
                        <span class="text-sm font-extrabold text-amber-700">{{ $tenant->trial_ends_at->diffForHumans() }}</span>
                    </div>
                    @endif
                    @endif
                </div>
            </div>

            <!-- Quick Stats -->
            <div class="glass-card p-6 rounded-[2rem]">
                <h3 class="text-base font-bold font-headline text-slate-900 mb-4">Payroll</h3>
                <div class="space-y-3">
                    <div class="flex items-center justify-between p-3 rounded-xl bg-emerald-50">
                        <div class="flex items-center gap-2">
                            <span class="material-symbols-outlined text-emerald-600 text-sm">account_balance_wallet</span>
                            <span class="text-sm font-semibold text-slate-700">Total Paid</span>
                        </div>
                        <span class="text-sm font-extrabold text-emerald-700">${{ number_format($totalPayrollCost, 0) }}</span>
                    </div>
                    @if($lastPayroll)
                    <div class="flex items-center justify-between p-3 rounded-xl bg-slate-50">
                        <div class="flex items-center gap-2">
                            <span class="material-symbols-outlined text-slate-500 text-sm">schedule</span>
                            <span class="text-sm font-semibold text-slate-700">Last Run</span>
                        </div>
                        <span class="text-sm font-extrabold text-slate-700">{{ $lastPayroll->created_at->format('M d') }}</span>
                    </div>
                    @endif
                </div>
            </div>

            <!-- Upcoming Leaves -->
            @if($upcomingLeaves->count() > 0)
            <div class="glass-card p-6 rounded-[2rem]">
                <h3 class="text-base font-bold font-headline text-slate-900 mb-4">Upcoming Leave</h3>
                <div class="space-y-2">
                    @foreach($upcomingLeaves as $leave)
                    <div class="flex items-center gap-3 p-3 rounded-xl bg-amber-50">
                        <span class="material-symbols-outlined text-amber-600 text-sm">event</span>
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-bold text-slate-800 truncate">{{ $leave->user->name }}</p>
                            <p class="text-xs text-slate-400">{{ $leave->start_date->format('M d') }} - {{ $leave->end_date->format('M d') }}</p>
                        </div>
                        <span class="text-[10px] font-bold bg-amber-100 text-amber-700 px-2 py-0.5 rounded-full">{{ $leave->total_days }}d</span>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

            <!-- Quick Actions -->
            <div class="glass-card p-6 rounded-[2rem] bg-gradient-to-br from-primary/5 to-white">
                <h3 class="text-base font-bold font-headline text-slate-900 mb-4">Quick Actions</h3>
                <div class="space-y-2">
                    <a href="{{ route('employees.create') }}" wire:navigate
                        class="flex items-center gap-3 p-3 rounded-2xl bg-white shadow-sm hover:shadow-md transition-shadow group">
                        <div class="w-8 h-8 rounded-xl bg-primary/10 flex items-center justify-center text-primary">
                            <span class="material-symbols-outlined text-sm">person_add</span>
                        </div>
                        <span class="text-sm font-bold text-slate-800">Add Employee</span>
                        <span class="material-symbols-outlined text-slate-300 group-hover:text-primary transition-colors text-sm ml-auto">arrow_forward</span>
                    </a>

                    @can('run payroll')
                    <a href="{{ route('payroll.run') }}" wire:navigate
                        class="flex items-center gap-3 p-3 rounded-2xl bg-white shadow-sm hover:shadow-md transition-shadow group">
                        <div class="w-8 h-8 rounded-xl bg-emerald-100 flex items-center justify-center text-emerald-600">
                            <span class="material-symbols-outlined text-sm">payments</span>
                        </div>
                        <span class="text-sm font-bold text-slate-800">Run Payroll</span>
                        <span class="material-symbols-outlined text-slate-300 group-hover:text-primary transition-colors text-sm ml-auto">arrow_forward</span>
                    </a>
                    @endcan

                    <a href="{{ route('departments.index') }}" wire:navigate
                        class="flex items-center gap-3 p-3 rounded-2xl bg-white shadow-sm hover:shadow-md transition-shadow group">
                        <div class="w-8 h-8 rounded-xl bg-primary/10 flex items-center justify-center text-primary">
                            <span class="material-symbols-outlined text-sm">domain</span>
                        </div>
                        <span class="text-sm font-bold text-slate-800">Departments</span>
                        <span class="material-symbols-outlined text-slate-300 group-hover:text-primary transition-colors text-sm ml-auto">arrow_forward</span>
                    </a>

                    @can('manage settings')
                    <a href="{{ route('announcements.index') }}" wire:navigate
                        class="flex items-center gap-3 p-3 rounded-2xl bg-white shadow-sm hover:shadow-md transition-shadow group">
                        <div class="w-8 h-8 rounded-xl bg-amber-100 flex items-center justify-center text-amber-600">
                            <span class="material-symbols-outlined text-sm">campaign</span>
                        </div>
                        <span class="text-sm font-bold text-slate-800">Announcements</span>
                        <span class="material-symbols-outlined text-slate-300 group-hover:text-primary transition-colors text-sm ml-auto">arrow_forward</span>
                    </a>
                    @endcan
                </div>
            </div>
        </div>
    </div>
</div>

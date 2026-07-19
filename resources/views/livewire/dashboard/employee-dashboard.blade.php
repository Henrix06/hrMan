<div>
    @if (session()->has('message'))
        <div class="mb-6 px-4 py-3 rounded-xl bg-green-50 border border-green-200 text-green-700 text-sm">{{ session('message') }}</div>
    @endif

    <!-- Welcome -->
    <section class="mb-10">
        <div class="flex flex-col md:flex-row md:items-end justify-between gap-6">
            <div>
                <h2 class="text-4xl font-extrabold text-slate-900 font-headline tracking-tight">
                    Good {{ now()->hour < 12 ? 'morning' : (now()->hour < 17 ? 'afternoon' : 'evening') }}, {{ explode(' ', auth()->user()->name)[0] }}.
                </h2>
                <p class="text-slate-500 font-body mt-2">Here's your work at a glance.</p>
            </div>
            @can('create employee')
            <a href="{{ route('employees.create') }}" wire:navigate
                class="bg-gradient-to-br from-primary to-primary-container text-on-primary px-6 py-3 rounded-xl font-bold font-headline shadow-lg shadow-primary/20 hover:opacity-90 transition-opacity flex items-center gap-2 w-fit">
                <span class="material-symbols-outlined text-sm">add</span> Add Employee
            </a>
            @endcan
        </div>

        <!-- Task Metric Cards -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-8">
            <!-- In Progress -->
            <a href="{{ route('tasks.index') }}" wire:navigate
                class="glass-card p-5 rounded-[2rem] shadow-sm relative overflow-hidden group hover:shadow-md transition-shadow">
                <div class="absolute -right-3 -top-3 w-20 h-20 bg-blue-500/5 rounded-full group-hover:scale-150 transition-transform duration-700"></div>
                <div class="w-9 h-9 rounded-xl bg-blue-100 flex items-center justify-center text-blue-600 mb-3">
                    <span class="material-symbols-outlined text-sm">pending_actions</span>
                </div>
                <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1">In Progress</p>
                <h3 class="text-4xl font-extrabold font-headline text-slate-900">{{ $taskMetrics['in_progress'] }}</h3>
            </a>

            <!-- Pending -->
            <a href="{{ route('tasks.index') }}" wire:navigate
                class="glass-card p-5 rounded-[2rem] shadow-sm relative overflow-hidden group hover:shadow-md transition-shadow">
                <div class="absolute -right-3 -top-3 w-20 h-20 bg-amber-500/5 rounded-full group-hover:scale-150 transition-transform duration-700"></div>
                <div class="w-9 h-9 rounded-xl bg-amber-100 flex items-center justify-center text-amber-600 mb-3">
                    <span class="material-symbols-outlined text-sm">schedule</span>
                </div>
                <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1">Pending</p>
                <h3 class="text-4xl font-extrabold font-headline text-slate-900">{{ $taskMetrics['pending'] }}</h3>
            </a>

            <!-- Completed -->
            <a href="{{ route('tasks.index') }}" wire:navigate
                class="glass-card p-5 rounded-[2rem] shadow-sm relative overflow-hidden group hover:shadow-md transition-shadow">
                <div class="absolute -right-3 -top-3 w-20 h-20 bg-green-500/5 rounded-full group-hover:scale-150 transition-transform duration-700"></div>
                <div class="w-9 h-9 rounded-xl bg-green-100 flex items-center justify-center text-green-600 mb-3">
                    <span class="material-symbols-outlined text-sm">task_alt</span>
                </div>
                <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1">Completed</p>
                <h3 class="text-4xl font-extrabold font-headline text-slate-900">{{ $taskMetrics['completed'] }}</h3>
            </a>

            <!-- Overdue / Team stat -->
            @if($teamStats)
            <a href="{{ route('tasks.index') }}" wire:navigate
                class="glass-card p-5 rounded-[2rem] shadow-sm relative overflow-hidden group hover:shadow-md transition-shadow {{ $teamStats['overdue_tasks'] > 0 ? 'border-l-4 border-error' : '' }}">
                <div class="absolute -right-3 -top-3 w-20 h-20 bg-red-500/5 rounded-full group-hover:scale-150 transition-transform duration-700"></div>
                <div class="w-9 h-9 rounded-xl {{ $teamStats['overdue_tasks'] > 0 ? 'bg-red-100 text-error' : 'bg-slate-100 text-slate-500' }} flex items-center justify-center mb-3">
                    <span class="material-symbols-outlined text-sm">groups</span>
                </div>
                <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1">Team Open</p>
                <h3 class="text-4xl font-extrabold font-headline text-slate-900">{{ $teamStats['open_tasks'] }}</h3>
                @if($teamStats['overdue_tasks'] > 0)
                <p class="text-[10px] text-error font-bold mt-1">{{ $teamStats['overdue_tasks'] }} overdue</p>
                @endif
            </a>
            @else
            <a href="{{ route('tasks.index') }}" wire:navigate
                class="glass-card p-5 rounded-[2rem] shadow-sm relative overflow-hidden group hover:shadow-md transition-shadow {{ $taskMetrics['overdue'] > 0 ? 'border-l-4 border-error' : '' }}">
                <div class="absolute -right-3 -top-3 w-20 h-20 bg-red-500/5 rounded-full group-hover:scale-150 transition-transform duration-700"></div>
                <div class="w-9 h-9 rounded-xl {{ $taskMetrics['overdue'] > 0 ? 'bg-red-100 text-error' : 'bg-slate-100 text-slate-500' }} flex items-center justify-center mb-3">
                    <span class="material-symbols-outlined text-sm">warning</span>
                </div>
                <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1">Overdue</p>
                <h3 class="text-4xl font-extrabold font-headline {{ $taskMetrics['overdue'] > 0 ? 'text-error' : 'text-slate-900' }}">{{ $taskMetrics['overdue'] }}</h3>
            </a>
            @endif
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

        <!-- My Tasks (main panel) -->
        <div class="lg:col-span-2 glass-card p-8 rounded-[2.5rem]">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h3 class="text-2xl font-bold font-headline text-slate-900">My Tasks</h3>
                    <p class="text-slate-500 text-sm">Active and upcoming work</p>
                </div>
                <a href="{{ route('tasks.index') }}" wire:navigate
                    class="text-sm font-bold text-primary hover:bg-primary/5 px-4 py-2 rounded-xl transition-colors">
                    View all
                </a>
            </div>

            @if($myTasks->count() > 0)
            <div class="space-y-0">
                @foreach($myTasks as $task)
                @php
                    $priorityColors = ['urgent'=>'bg-red-100 text-red-700','high'=>'bg-orange-100 text-orange-700','medium'=>'bg-amber-100 text-amber-700','low'=>'bg-slate-100 text-slate-500'];
                    $statusColors   = ['pending'=>'bg-amber-100 text-amber-700','in_progress'=>'bg-blue-100 text-blue-700'];
                    $overdue = $task->due_date && $task->due_date->isPast();
                @endphp
                <div class="flex items-center gap-4 py-4 border-b border-slate-100 last:border-0 hover:bg-slate-50/50 px-3 -mx-3 rounded-xl transition-colors">
                    <div class="w-9 h-9 rounded-xl flex items-center justify-center flex-shrink-0
                        {{ $task->status === 'in_progress' ? 'bg-blue-100 text-blue-600' : 'bg-amber-100 text-amber-600' }}">
                        <span class="material-symbols-outlined text-sm">{{ $task->status === 'in_progress' ? 'pending_actions' : 'schedule' }}</span>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-bold text-slate-800 truncate">{{ $task->title }}</p>
                        <div class="flex items-center gap-2 mt-0.5">
                            <span class="text-[10px] font-bold px-1.5 py-0.5 rounded-full {{ $priorityColors[$task->priority] }}">{{ $task->priority }}</span>
                            @if($task->due_date)
                            <span class="text-xs {{ $overdue ? 'text-error font-bold' : 'text-slate-400' }}">
                                {{ $overdue ? 'Overdue · ' : 'Due ' }}{{ $task->due_date->format('M d') }}
                            </span>
                            @endif
                        </div>
                    </div>
                    <span class="px-2.5 py-1 rounded-full text-[10px] font-bold uppercase tracking-widest flex-shrink-0 {{ $statusColors[$task->status] ?? 'bg-slate-100 text-slate-500' }}">
                        {{ str_replace('_', ' ', $task->status) }}
                    </span>
                </div>
                @endforeach
            </div>
            @else
            <div class="flex flex-col items-center justify-center py-14 text-slate-400">
                <span class="material-symbols-outlined text-5xl mb-3">task_alt</span>
                <p class="text-sm">No active tasks. You're all caught up.</p>
                <a href="{{ route('tasks.index') }}" wire:navigate class="mt-3 text-sm font-bold text-primary hover:underline">Create a task</a>
            </div>
            @endif
        </div>

        <!-- Right Column -->
        <div class="space-y-6">

            <!-- Leave & Payslips quick stats -->
            <div class="glass-card p-6 rounded-[2rem]">
                <h3 class="text-base font-bold font-headline text-slate-900 mb-4">Quick Stats</h3>
                <div class="space-y-3">
                    <div class="flex items-center justify-between p-3 rounded-xl bg-amber-50">
                        <div class="flex items-center gap-2">
                            <span class="material-symbols-outlined text-amber-600 text-sm">calendar_today</span>
                            <span class="text-sm font-semibold text-slate-700">Pending Leave</span>
                        </div>
                        <span class="text-sm font-extrabold text-amber-700">{{ $pendingLeaves }}</span>
                    </div>
                    <div class="flex items-center justify-between p-3 rounded-xl bg-slate-50">
                        <div class="flex items-center gap-2">
                            <span class="material-symbols-outlined text-slate-500 text-sm">receipt_long</span>
                            <span class="text-sm font-semibold text-slate-700">Payslips</span>
                        </div>
                        <span class="text-sm font-extrabold text-slate-700">{{ $recentPayslips->count() }}</span>
                    </div>
                    @if($teamStats)
                    <div class="flex items-center justify-between p-3 rounded-xl bg-primary/5">
                        <div class="flex items-center gap-2">
                            <span class="material-symbols-outlined text-primary text-sm">groups</span>
                            <span class="text-sm font-semibold text-slate-700">Team Members</span>
                        </div>
                        <span class="text-sm font-extrabold text-primary">{{ $teamStats['total_members'] }}</span>
                    </div>
                    @endif
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="glass-card p-6 rounded-[2rem] bg-gradient-to-br from-primary/5 to-white">
                <h3 class="text-base font-bold font-headline text-slate-900 mb-4">Quick Actions</h3>
                <div class="space-y-2">
                    <a href="{{ route('tasks.index') }}" wire:navigate
                        class="flex items-center gap-3 p-3 rounded-2xl bg-white shadow-sm hover:shadow-md transition-shadow group">
                        <div class="w-8 h-8 rounded-xl bg-blue-100 flex items-center justify-center text-blue-600">
                            <span class="material-symbols-outlined text-sm">add_task</span>
                        </div>
                        <span class="text-sm font-bold text-slate-800">New Task</span>
                        <span class="material-symbols-outlined text-slate-300 group-hover:text-primary transition-colors text-sm ml-auto">arrow_forward</span>
                    </a>

                    <a href="{{ route('leave.apply') }}" wire:navigate
                        class="flex items-center gap-3 p-3 rounded-2xl bg-white shadow-sm hover:shadow-md transition-shadow group">
                        <div class="w-8 h-8 rounded-xl bg-amber-100 flex items-center justify-center text-amber-600">
                            <span class="material-symbols-outlined text-sm">add_circle</span>
                        </div>
                        <span class="text-sm font-bold text-slate-800">Apply for Leave</span>
                        <span class="material-symbols-outlined text-slate-300 group-hover:text-primary transition-colors text-sm ml-auto">arrow_forward</span>
                    </a>

                    @can('approve leave')
                    <a href="{{ route('leave.approval') }}" wire:navigate
                        class="flex items-center gap-3 p-3 rounded-2xl bg-white shadow-sm hover:shadow-md transition-shadow group">
                        <div class="w-8 h-8 rounded-xl bg-green-100 flex items-center justify-center text-green-600">
                            <span class="material-symbols-outlined text-sm">how_to_reg</span>
                        </div>
                        <span class="text-sm font-bold text-slate-800">Leave Approvals</span>
                        @if($teamStats && $teamStats['pending_approvals'] > 0)
                        <span class="ml-auto text-[10px] font-bold bg-error text-white px-2 py-0.5 rounded-full">{{ $teamStats['pending_approvals'] }}</span>
                        @else
                        <span class="material-symbols-outlined text-slate-300 group-hover:text-primary transition-colors text-sm ml-auto">arrow_forward</span>
                        @endif
                    </a>
                    @endcan

                    @can('run payroll')
                    <a href="{{ route('payroll.run') }}" wire:navigate
                        class="flex items-center gap-3 p-3 rounded-2xl bg-white shadow-sm hover:shadow-md transition-shadow group">
                        <div class="w-8 h-8 rounded-xl bg-tertiary/10 flex items-center justify-center text-tertiary">
                            <span class="material-symbols-outlined text-sm">payments</span>
                        </div>
                        <span class="text-sm font-bold text-slate-800">Run Payroll</span>
                        <span class="material-symbols-outlined text-slate-300 group-hover:text-primary transition-colors text-sm ml-auto">arrow_forward</span>
                    </a>
                    @endcan

                    <a href="{{ route('payslips.index') }}" wire:navigate
                        class="flex items-center gap-3 p-3 rounded-2xl bg-white shadow-sm hover:shadow-md transition-shadow group">
                        <div class="w-8 h-8 rounded-xl bg-primary/10 flex items-center justify-center text-primary">
                            <span class="material-symbols-outlined text-sm">receipt_long</span>
                        </div>
                        <span class="text-sm font-bold text-slate-800">View Payslips</span>
                        <span class="material-symbols-outlined text-slate-300 group-hover:text-primary transition-colors text-sm ml-auto">arrow_forward</span>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php

use App\Livewire\Actions\Logout;
use Livewire\Volt\Component;

new class extends Component
{
    public bool $sidebarOpen = false;

    public function logout(Logout $logout): void
    {
        $logout();
        $this->redirect('/', navigate: true);
    }
}; ?>

<div x-data="{ sidebarOpen: false }">
<!-- Mobile overlay -->
<div x-show="sidebarOpen" x-cloak
    class="fixed inset-0 bg-black/50 backdrop-blur-sm z-40 lg:hidden"
    x-transition:enter="transition-opacity ease-out duration-300"
    x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100"
    x-transition:leave="transition-opacity ease-in duration-200"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"
    @click="sidebarOpen = false">
</div>

<!-- Sidebar -->
<aside :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'"
    class="fixed left-0 top-0 h-full z-50 w-64 flex flex-col border-r border-white/10 dark:border-white/5 bg-slate-100/70 dark:bg-slate-900/80 backdrop-blur-xl shadow-[32px_0_32px_rgba(78,69,228,0.04)] transition-transform duration-300 ease-in-out">
    <div class="p-6 lg:p-8">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-primary to-primary-container flex items-center justify-center text-on-primary shadow-lg shadow-primary/20">
                    <span class="material-symbols-outlined">bubble_chart</span>
                </div>
                <div>
                    <h1 class="text-xl font-bold tracking-tight text-primary dark:text-primary-container font-headline">uMsizi</h1>
                    <p class="text-xs text-slate-500 dark:text-slate-400">Fluid Professional</p>
                </div>
            </div>
            <button @click="sidebarOpen = false" class="lg:hidden p-1 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-200/50 transition-colors">
                <span class="material-symbols-outlined text-xl">close</span>
            </button>
        </div>
    </div>

    <nav class="flex-1 px-4 overflow-y-auto space-y-5 py-2">

        {{-- General --}}
        <div>
            <a href="{{ route('dashboard') }}" wire:navigate
                class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all duration-300 {{ request()->routeIs('dashboard') ? 'bg-white/80 dark:bg-white/10 text-primary dark:text-primary-container font-bold shadow-sm' : 'text-slate-500 dark:text-slate-400 hover:text-primary hover:bg-white/40 dark:hover:bg-white/5' }}">
                <span class="material-symbols-outlined">dashboard</span>
                <span class="font-headline">Dashboard</span>
            </a>
        </div>

        {{-- People --}}
        @role('Manager|HR Admin|Super Admin')
        <div>
            <p class="px-4 mb-1 text-[10px] font-bold uppercase tracking-widest text-slate-400 dark:text-slate-500">People</p>
            <a href="{{ route('employees.index') }}" wire:navigate
                class="flex items-center gap-3 px-4 py-2.5 rounded-xl transition-all duration-300 {{ request()->routeIs('employees.*') ? 'bg-white/80 dark:bg-white/10 text-primary dark:text-primary-container font-bold shadow-sm' : 'text-slate-500 dark:text-slate-400 hover:text-primary hover:bg-white/40 dark:hover:bg-white/5' }}">
                <span class="material-symbols-outlined text-[20px]">groups</span>
                <span class="font-headline text-sm">Employees</span>
            </a>
            @role('HR Admin|Super Admin')
            <a href="{{ route('departments.index') }}" wire:navigate
                class="flex items-center gap-3 px-4 py-2.5 rounded-xl transition-all duration-300 {{ request()->routeIs('departments.*') ? 'bg-white/80 dark:bg-white/10 text-primary dark:text-primary-container font-bold shadow-sm' : 'text-slate-500 dark:text-slate-400 hover:text-primary hover:bg-white/40 dark:hover:bg-white/5' }}">
                <span class="material-symbols-outlined text-[20px]">corporate_fare</span>
                <span class="font-headline text-sm">Departments</span>
            </a>
            @endrole
        </div>
        @endrole

        {{-- Time Off --}}
        <div>
            <p class="px-4 mb-1 text-[10px] font-bold uppercase tracking-widest text-slate-400 dark:text-slate-500">Time Off</p>
            <a href="{{ route('leave.apply') }}" wire:navigate
                class="flex items-center gap-3 px-4 py-2.5 rounded-xl transition-all duration-300 {{ request()->routeIs('leave.apply') || request()->routeIs('leave.balance') ? 'bg-white/80 dark:bg-white/10 text-primary dark:text-primary-container font-bold shadow-sm' : 'text-slate-500 dark:text-slate-400 hover:text-primary hover:bg-white/40 dark:hover:bg-white/5' }}">
                <span class="material-symbols-outlined text-[20px]">calendar_today</span>
                <span class="font-headline text-sm">My Leave</span>
            </a>
            @role('Manager|HR Admin|Super Admin')
            <a href="{{ route('leave.approval') }}" wire:navigate
                class="flex items-center gap-3 px-4 py-2.5 rounded-xl transition-all duration-300 {{ request()->routeIs('leave.approval') ? 'bg-white/80 dark:bg-white/10 text-primary dark:text-primary-container font-bold shadow-sm' : 'text-slate-500 dark:text-slate-400 hover:text-primary hover:bg-white/40 dark:hover:bg-white/5' }}">
                <span class="material-symbols-outlined text-[20px]">how_to_reg</span>
                <span class="font-headline text-sm">Approvals</span>
            </a>
            @endrole
        </div>

        {{-- Work --}}
        <div>
            <p class="px-4 mb-1 text-[10px] font-bold uppercase tracking-widest text-slate-400 dark:text-slate-500">Work</p>
            <a href="{{ route('tasks.index') }}" wire:navigate
                class="flex items-center gap-3 px-4 py-2.5 rounded-xl transition-all duration-300 {{ request()->routeIs('tasks.*') ? 'bg-white/80 dark:bg-white/10 text-primary dark:text-primary-container font-bold shadow-sm' : 'text-slate-500 dark:text-slate-400 hover:text-primary hover:bg-white/40 dark:hover:bg-white/5' }}">
                <span class="material-symbols-outlined text-[20px]">checklist</span>
                <span class="font-headline text-sm">Tasks</span>
            </a>
            <a href="{{ route('announcements.index') }}" wire:navigate
                class="flex items-center gap-3 px-4 py-2.5 rounded-xl transition-all duration-300 {{ request()->routeIs('announcements.*') ? 'bg-white/80 dark:bg-white/10 text-primary dark:text-primary-container font-bold shadow-sm' : 'text-slate-500 dark:text-slate-400 hover:text-primary hover:bg-white/40 dark:hover:bg-white/5' }}">
                <span class="material-symbols-outlined text-[20px]">campaign</span>
                <span class="font-headline text-sm">Announcements</span>
            </a>
            <a href="{{ route('folders.index') }}" wire:navigate
                class="flex items-center gap-3 px-4 py-2.5 rounded-xl transition-all duration-300 {{ request()->routeIs('folders.*') ? 'bg-white/80 dark:bg-white/10 text-primary dark:text-primary-container font-bold shadow-sm' : 'text-slate-500 dark:text-slate-400 hover:text-primary hover:bg-white/40 dark:hover:bg-white/5' }}">
                <span class="material-symbols-outlined text-[20px]">folder_shared</span>
                <span class="font-headline text-sm">Folders</span>
            </a>
            @role('Manager|HR Admin|Super Admin')
            <a href="{{ route('assets.index') }}" wire:navigate
                class="flex items-center gap-3 px-4 py-2.5 rounded-xl transition-all duration-300 {{ request()->routeIs('assets.index') ? 'bg-white/80 dark:bg-white/10 text-primary dark:text-primary-container font-bold shadow-sm' : 'text-slate-500 dark:text-slate-400 hover:text-primary hover:bg-white/40 dark:hover:bg-white/5' }}">
                <span class="material-symbols-outlined text-[20px]">inventory_2</span>
                <span class="font-headline text-sm">Assets</span>
            </a>
            @endrole
            @role('Employee')
            <a href="{{ route('assets.my') }}" wire:navigate
                class="flex items-center gap-3 px-4 py-2.5 rounded-xl transition-all duration-300 {{ request()->routeIs('assets.my') ? 'bg-white/80 dark:bg-white/10 text-primary dark:text-primary-container font-bold shadow-sm' : 'text-slate-500 dark:text-slate-400 hover:text-primary hover:bg-white/40 dark:hover:bg-white/5' }}">
                <span class="material-symbols-outlined text-[20px]">inventory_2</span>
                <span class="font-headline text-sm">My Assets</span>
            </a>
            @endrole
        </div>

        {{-- Sales --}}
        @can('view sales')
        <div>
            <p class="px-4 mb-1 text-[10px] font-bold uppercase tracking-widest text-slate-400 dark:text-slate-500">Sales</p>
            <a href="{{ route('sales.orders') }}" wire:navigate
                class="flex items-center gap-3 px-4 py-2.5 rounded-xl transition-all duration-300 {{ request()->routeIs('sales.orders') ? 'bg-white/80 dark:bg-white/10 text-primary dark:text-primary-container font-bold shadow-sm' : 'text-slate-500 dark:text-slate-400 hover:text-primary hover:bg-white/40 dark:hover:bg-white/5' }}">
                <span class="material-symbols-outlined text-[20px]">point_of_sale</span>
                <span class="font-headline text-sm">Orders</span>
            </a>
            <a href="{{ route('sales.transport') }}" wire:navigate
                class="flex items-center gap-3 px-4 py-2.5 rounded-xl transition-all duration-300 {{ request()->routeIs('sales.transport') ? 'bg-white/80 dark:bg-white/10 text-primary dark:text-primary-container font-bold shadow-sm' : 'text-slate-500 dark:text-slate-400 hover:text-primary hover:bg-white/40 dark:hover:bg-white/5' }}">
                <span class="material-symbols-outlined text-[20px]">local_shipping</span>
                <span class="font-headline text-sm">Transport Requests</span>
            </a>
        </div>
        @endcan

        {{-- Accounts --}}
        @can('view accounts')
        <div>
            <p class="px-4 mb-1 text-[10px] font-bold uppercase tracking-widest text-slate-400 dark:text-slate-500">Accounts</p>
            @can('manage accounts')
            <a href="{{ route('accounts.index') }}" wire:navigate
                class="flex items-center gap-3 px-4 py-2.5 rounded-xl transition-all duration-300 {{ request()->routeIs('accounts.index') ? 'bg-white/80 dark:bg-white/10 text-primary dark:text-primary-container font-bold shadow-sm' : 'text-slate-500 dark:text-slate-400 hover:text-primary hover:bg-white/40 dark:hover:bg-white/5' }}">
                <span class="material-symbols-outlined text-[20px]">account_balance_wallet</span>
                <span class="font-headline text-sm">Fund Accounts</span>
            </a>
            @endcan
            <a href="{{ route('accounts.procurement') }}" wire:navigate
                class="flex items-center gap-3 px-4 py-2.5 rounded-xl transition-all duration-300 {{ request()->routeIs('accounts.procurement') ? 'bg-white/80 dark:bg-white/10 text-primary dark:text-primary-container font-bold shadow-sm' : 'text-slate-500 dark:text-slate-400 hover:text-primary hover:bg-white/40 dark:hover:bg-white/5' }}">
                <span class="material-symbols-outlined text-[20px]">shopping_cart</span>
                <span class="font-headline text-sm">Procurement</span>
            </a>
            <a href="{{ route('accounts.overtime') }}" wire:navigate
                class="flex items-center gap-3 px-4 py-2.5 rounded-xl transition-all duration-300 {{ request()->routeIs('accounts.overtime') ? 'bg-white/80 dark:bg-white/10 text-primary dark:text-primary-container font-bold shadow-sm' : 'text-slate-500 dark:text-slate-400 hover:text-primary hover:bg-white/40 dark:hover:bg-white/5' }}">
                <span class="material-symbols-outlined text-[20px]">schedule</span>
                <span class="font-headline text-sm">Overtime</span>
            </a>
            <a href="{{ route('accounts.transport') }}" wire:navigate
                class="flex items-center gap-3 px-4 py-2.5 rounded-xl transition-all duration-300 {{ request()->routeIs('accounts.transport') ? 'bg-white/80 dark:bg-white/10 text-primary dark:text-primary-container font-bold shadow-sm' : 'text-slate-500 dark:text-slate-400 hover:text-primary hover:bg-white/40 dark:hover:bg-white/5' }}">
                <span class="material-symbols-outlined text-[20px]">directions_car</span>
                <span class="font-headline text-sm">Transport</span>
            </a>
        </div>
        @endcan

        {{-- Payroll --}}
        <div>
            <p class="px-4 mb-1 text-[10px] font-bold uppercase tracking-widest text-slate-400 dark:text-slate-500">Payroll</p>
            @role('HR Admin|Super Admin')
            <a href="{{ route('payroll.run') }}" wire:navigate
                class="flex items-center gap-3 px-4 py-2.5 rounded-xl transition-all duration-300 {{ request()->routeIs('payroll.*') ? 'bg-white/80 dark:bg-white/10 text-primary dark:text-primary-container font-bold shadow-sm' : 'text-slate-500 dark:text-slate-400 hover:text-primary hover:bg-white/40 dark:hover:bg-white/5' }}">
                <span class="material-symbols-outlined text-[20px]">payments</span>
                <span class="font-headline text-sm">Run Payroll</span>
            </a>
            @endrole
            <a href="{{ route('payslips.index') }}" wire:navigate
                class="flex items-center gap-3 px-4 py-2.5 rounded-xl transition-all duration-300 {{ request()->routeIs('payslips.*') ? 'bg-white/80 dark:bg-white/10 text-primary dark:text-primary-container font-bold shadow-sm' : 'text-slate-500 dark:text-slate-400 hover:text-primary hover:bg-white/40 dark:hover:bg-white/5' }}">
                <span class="material-symbols-outlined text-[20px]">receipt_long</span>
                <span class="font-headline text-sm">Payslips</span>
            </a>
        </div>

        {{-- Admin --}}
        @role('Super Admin')
        <div>
            <p class="px-4 mb-1 text-[10px] font-bold uppercase tracking-widest text-slate-400 dark:text-slate-500">Admin</p>
            <a href="{{ route('team.index') }}" wire:navigate
                class="flex items-center gap-3 px-4 py-2.5 rounded-xl transition-all duration-300 {{ request()->routeIs('team.*') ? 'bg-white/80 dark:bg-white/10 text-primary dark:text-primary-container font-bold shadow-sm' : 'text-slate-500 dark:text-slate-400 hover:text-primary hover:bg-white/40 dark:hover:bg-white/5' }}">
                <span class="material-symbols-outlined text-[20px]">group_add</span>
                <span class="font-headline text-sm">Team</span>
            </a>
            <a href="{{ route('admin.audit-logs') }}" wire:navigate
                class="flex items-center gap-3 px-4 py-2.5 rounded-xl transition-all duration-300 {{ request()->routeIs('admin.*') ? 'bg-white/80 dark:bg-white/10 text-primary dark:text-primary-container font-bold shadow-sm' : 'text-slate-500 dark:text-slate-400 hover:text-primary hover:bg-white/40 dark:hover:bg-white/5' }}">
                <span class="material-symbols-outlined text-[20px]">manage_history</span>
                <span class="font-headline text-sm">Audit Logs</span>
            </a>
            <a href="{{ route('plan.index') }}" wire:navigate
                class="flex items-center gap-3 px-4 py-2.5 rounded-xl transition-all duration-300 {{ request()->routeIs('plan.*') ? 'bg-white/80 dark:bg-white/10 text-primary dark:text-primary-container font-bold shadow-sm' : 'text-slate-500 dark:text-slate-400 hover:text-primary hover:bg-white/40 dark:hover:bg-white/5' }}">
                <span class="material-symbols-outlined text-[20px]">workspace_premium</span>
                <span class="font-headline text-sm">Plan &amp; Modules</span>
            </a>
            <a href="{{ route('license.index') }}" wire:navigate
                class="flex items-center gap-3 px-4 py-2.5 rounded-xl transition-all duration-300 {{ request()->routeIs('license.*') ? 'bg-white/80 dark:bg-white/10 text-primary dark:text-primary-container font-bold shadow-sm' : 'text-slate-500 dark:text-slate-400 hover:text-primary hover:bg-white/40 dark:hover:bg-white/5' }}">
                <span class="material-symbols-outlined text-[20px]">key</span>
                <span class="font-headline text-sm">Licence</span>
            </a>
        </div>
        @endrole

    </nav>

    <div class="px-4 py-6 space-y-1 border-t border-slate-200/50 dark:border-white/5">
        <a href="{{ route('profile') }}" wire:navigate
            class="flex items-center gap-3 px-4 py-2 text-slate-500 dark:text-slate-400 hover:text-primary transition-all rounded-xl">
            <span class="material-symbols-outlined">settings</span>
            <span class="text-sm font-headline">Settings</span>
        </a>
        <button wire:click="logout"
            class="w-full flex items-center gap-3 px-4 py-2 text-slate-500 dark:text-slate-400 hover:text-red-500 transition-all rounded-xl">
            <span class="material-symbols-outlined">logout</span>
            <span class="text-sm font-headline">Log Out</span>
        </button>
        <div class="pt-3 mt-1 border-t border-slate-200/40 dark:border-white/5">
            <a href="https://henrix.co.zw" target="_blank" rel="noopener noreferrer"
                class="flex items-center justify-center gap-1.5 text-[10px] text-slate-400 dark:text-slate-600 hover:text-primary dark:hover:text-primary-container transition-colors">
                <span class="material-symbols-outlined text-[12px]">bolt</span>
                Powered by Henrix
            </a>
        </div>
    </div>
</aside>

<!-- Top Bar -->
<header class="fixed top-0 right-0 left-0 lg:left-64 h-16 px-4 lg:px-8 flex justify-between items-center z-30 bg-white/60 dark:bg-slate-900/70 backdrop-blur-2xl border-b border-white/40 dark:border-white/5">
    <div class="flex items-center gap-3">
        {{-- Mobile hamburger --}}
        <button @click="sidebarOpen = !sidebarOpen" class="lg:hidden p-2 rounded-xl text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">
            <span class="material-symbols-outlined">menu</span>
        </button>
        {{-- Search (hidden on mobile) --}}
        <div class="hidden md:flex items-center flex-1 max-w-xl">
            <div class="relative w-full">
                <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-sm">search</span>
                <input class="w-full pl-10 pr-4 py-2 bg-surface-container-low dark:bg-slate-800 dark:text-slate-200 dark:placeholder-slate-500 border-none rounded-xl text-sm focus:ring-2 focus:ring-primary/20 transition-all"
                    placeholder="Search employees, records..." type="text"/>
            </div>
        </div>
    </div>

    <div class="flex items-center gap-2 lg:gap-3 lg:border-l lg:border-slate-200 dark:border-white/10 lg:pl-6 lg:ml-6">

        {{-- Dark mode toggle --}}
        <button @click="dark = !dark"
            class="w-9 h-9 flex items-center justify-center rounded-xl text-slate-500 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors"
            :title="dark ? 'Switch to light mode' : 'Switch to dark mode'">
            <span class="material-symbols-outlined text-sm" x-show="!dark">dark_mode</span>
            <span class="material-symbols-outlined text-sm" x-show="dark" x-cloak>light_mode</span>
        </button>

        {{-- Notifications --}}
        @php
            $unread = auth()->user()->unreadNotificationsCount();
            $recentNotifs = \App\Models\HrNotification::where('user_id', auth()->id())
                ->orderByDesc('created_at')
                ->limit(6)
                ->get();
        @endphp
        <div class="relative" x-data="{ open: false }" @mouseenter="open = true" @mouseleave="open = false">
            <button
                class="relative w-9 h-9 flex items-center justify-center rounded-xl text-slate-500 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors"
                onclick="window.location='{{ route('notifications.index') }}'">
                <span class="material-symbols-outlined text-sm">notifications</span>
                @if($unread > 0)
                <span class="absolute top-1 right-1 w-4 h-4 bg-error rounded-full border-2 border-white dark:border-slate-900 text-white text-[9px] font-bold flex items-center justify-center">{{ $unread > 9 ? '9+' : $unread }}</span>
                @endif
            </button>

            {{-- Hover dropdown --}}
            <div x-show="open" x-cloak
                x-transition:enter="transition ease-out duration-150"
                x-transition:enter-start="opacity-0 translate-y-1"
                x-transition:enter-end="opacity-100 translate-y-0"
                x-transition:leave="transition ease-in duration-100"
                x-transition:leave-start="opacity-100 translate-y-0"
                x-transition:leave-end="opacity-0 translate-y-1"
                class="absolute right-0 top-full mt-2 w-80 bg-white dark:bg-slate-900 rounded-2xl shadow-2xl shadow-slate-900/10 border border-slate-100 dark:border-white/5 z-50 overflow-hidden">

                <div class="flex items-center justify-between px-4 py-3 border-b border-slate-100 dark:border-white/5">
                    <span class="text-sm font-bold text-slate-800 dark:text-slate-100 font-headline">Notifications</span>
                    @if($unread > 0)
                    <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-error/10 text-error">{{ $unread }} unread</span>
                    @else
                    <span class="text-[10px] text-slate-400">All caught up</span>
                    @endif
                </div>

                <div class="max-h-72 overflow-y-auto divide-y divide-slate-50 dark:divide-white/5">
                    @forelse($recentNotifs as $notif)
                    @php
                        $icons = ['task_assigned'=>'task_alt','task_comment'=>'comment','task_mention'=>'alternate_email','task_status'=>'swap_horiz','sales_order'=>'point_of_sale','transport_requested'=>'local_shipping','transport_status'=>'local_shipping','announcement'=>'campaign','leave_approved'=>'check_circle','leave_rejected'=>'cancel','folder_shared'=>'folder_shared','default'=>'notifications'];
                        $icon = $icons[$notif->type] ?? $icons['default'];
                    @endphp
                    <div class="flex items-start gap-3 px-4 py-3 {{ !$notif->isRead() ? 'bg-primary/[0.03]' : '' }} hover:bg-slate-50 dark:hover:bg-white/5 transition-colors">
                        <div class="w-8 h-8 rounded-full bg-primary/10 flex items-center justify-center text-primary flex-shrink-0 mt-0.5">
                            <span class="material-symbols-outlined text-[14px]">{{ $icon }}</span>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-xs font-semibold text-slate-800 dark:text-slate-100 leading-snug">{{ $notif->title }}</p>
                            <p class="text-[11px] text-slate-400 mt-0.5 line-clamp-2 leading-relaxed">{{ $notif->message }}</p>
                            <p class="text-[10px] text-slate-300 dark:text-slate-600 mt-1">{{ $notif->created_at->diffForHumans() }}</p>
                        </div>
                        @if(!$notif->isRead())
                        <div class="w-2 h-2 rounded-full bg-primary flex-shrink-0 mt-1.5"></div>
                        @endif
                    </div>
                    @empty
                    <div class="py-10 text-center text-slate-400">
                        <span class="material-symbols-outlined text-3xl block mb-2 opacity-40">notifications_none</span>
                        <p class="text-xs">No notifications yet.</p>
                    </div>
                    @endforelse
                </div>

                <div class="px-4 py-2.5 border-t border-slate-100 dark:border-white/5 bg-slate-50/50 dark:bg-white/5">
                    <a href="{{ route('notifications.index') }}"
                        class="text-xs font-bold text-primary hover:underline flex items-center justify-center gap-1">
                        View all notifications
                        <span class="material-symbols-outlined text-[13px]">arrow_forward</span>
                    </a>
                </div>
            </div>
        </div>

        {{-- User --}}
        <div class="flex items-center gap-3">
            <div class="w-9 h-9 rounded-full bg-gradient-to-br from-primary to-primary-container flex items-center justify-center text-white font-bold text-sm shadow-sm">
                {{ strtoupper(substr(auth()->user()->name, 0, 2)) }}
            </div>
            <div class="hidden lg:block">
                <p class="text-sm font-bold text-slate-900 dark:text-slate-100 font-headline leading-none">{{ auth()->user()->name }}</p>
                <p class="text-[10px] text-slate-500 dark:text-slate-400 uppercase tracking-widest">{{ auth()->user()->roles->first()?->name ?? 'Employee' }}</p>
            </div>
        </div>
    </div>
</header>
</div>

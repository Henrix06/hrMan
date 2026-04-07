<?php

use App\Livewire\Actions\Logout;
use Livewire\Volt\Component;

new class extends Component
{
    public function logout(Logout $logout): void
    {
        $logout();
        $this->redirect('/', navigate: true);
    }
}; ?>

<div>
<!-- Sidebar -->
<aside class="fixed left-0 top-0 h-full z-40 w-64 flex flex-col border-r border-white/10 dark:border-white/5 bg-slate-100/70 dark:bg-slate-900/80 backdrop-blur-xl shadow-[32px_0_32px_rgba(78,69,228,0.04)]">
    <div class="p-8">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-primary to-primary-container flex items-center justify-center text-on-primary shadow-lg shadow-primary/20">
                <span class="material-symbols-outlined">bubble_chart</span>
            </div>
            <div>
                <h1 class="text-xl font-bold tracking-tight text-indigo-700 dark:text-indigo-400 font-headline">Ethereal HR</h1>
                <p class="text-xs text-slate-500 dark:text-slate-400">Fluid Professional</p>
            </div>
        </div>
    </div>

    <nav class="flex-1 px-4 overflow-y-auto space-y-5 py-2">

        {{-- General --}}
        <div>
            <a href="{{ route('dashboard') }}" wire:navigate
                class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all duration-300 {{ request()->routeIs('dashboard') ? 'bg-white/80 dark:bg-white/10 text-indigo-600 dark:text-indigo-400 font-bold shadow-sm' : 'text-slate-500 dark:text-slate-400 hover:text-indigo-500 hover:bg-white/40 dark:hover:bg-white/5' }}">
                <span class="material-symbols-outlined">dashboard</span>
                <span class="font-headline">Dashboard</span>
            </a>
        </div>

        {{-- People --}}
        @role('Manager|HR Admin|Super Admin')
        <div>
            <p class="px-4 mb-1 text-[10px] font-bold uppercase tracking-widest text-slate-400 dark:text-slate-500">People</p>
            <a href="{{ route('employees.index') }}" wire:navigate
                class="flex items-center gap-3 px-4 py-2.5 rounded-xl transition-all duration-300 {{ request()->routeIs('employees.*') ? 'bg-white/80 dark:bg-white/10 text-indigo-600 dark:text-indigo-400 font-bold shadow-sm' : 'text-slate-500 dark:text-slate-400 hover:text-indigo-500 hover:bg-white/40 dark:hover:bg-white/5' }}">
                <span class="material-symbols-outlined text-[20px]">groups</span>
                <span class="font-headline text-sm">Employees</span>
            </a>
            @role('HR Admin|Super Admin')
            <a href="{{ route('departments.index') }}" wire:navigate
                class="flex items-center gap-3 px-4 py-2.5 rounded-xl transition-all duration-300 {{ request()->routeIs('departments.*') ? 'bg-white/80 dark:bg-white/10 text-indigo-600 dark:text-indigo-400 font-bold shadow-sm' : 'text-slate-500 dark:text-slate-400 hover:text-indigo-500 hover:bg-white/40 dark:hover:bg-white/5' }}">
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
                class="flex items-center gap-3 px-4 py-2.5 rounded-xl transition-all duration-300 {{ request()->routeIs('leave.apply') || request()->routeIs('leave.balance') ? 'bg-white/80 dark:bg-white/10 text-indigo-600 dark:text-indigo-400 font-bold shadow-sm' : 'text-slate-500 dark:text-slate-400 hover:text-indigo-500 hover:bg-white/40 dark:hover:bg-white/5' }}">
                <span class="material-symbols-outlined text-[20px]">calendar_today</span>
                <span class="font-headline text-sm">My Leave</span>
            </a>
            @role('Manager|HR Admin|Super Admin')
            <a href="{{ route('leave.approval') }}" wire:navigate
                class="flex items-center gap-3 px-4 py-2.5 rounded-xl transition-all duration-300 {{ request()->routeIs('leave.approval') ? 'bg-white/80 dark:bg-white/10 text-indigo-600 dark:text-indigo-400 font-bold shadow-sm' : 'text-slate-500 dark:text-slate-400 hover:text-indigo-500 hover:bg-white/40 dark:hover:bg-white/5' }}">
                <span class="material-symbols-outlined text-[20px]">how_to_reg</span>
                <span class="font-headline text-sm">Approvals</span>
            </a>
            @endrole
        </div>

        {{-- Work --}}
        <div>
            <p class="px-4 mb-1 text-[10px] font-bold uppercase tracking-widest text-slate-400 dark:text-slate-500">Work</p>
            <a href="{{ route('tasks.index') }}" wire:navigate
                class="flex items-center gap-3 px-4 py-2.5 rounded-xl transition-all duration-300 {{ request()->routeIs('tasks.*') ? 'bg-white/80 dark:bg-white/10 text-indigo-600 dark:text-indigo-400 font-bold shadow-sm' : 'text-slate-500 dark:text-slate-400 hover:text-indigo-500 hover:bg-white/40 dark:hover:bg-white/5' }}">
                <span class="material-symbols-outlined text-[20px]">checklist</span>
                <span class="font-headline text-sm">Tasks</span>
            </a>
            <a href="{{ route('announcements.index') }}" wire:navigate
                class="flex items-center gap-3 px-4 py-2.5 rounded-xl transition-all duration-300 {{ request()->routeIs('announcements.*') ? 'bg-white/80 dark:bg-white/10 text-indigo-600 dark:text-indigo-400 font-bold shadow-sm' : 'text-slate-500 dark:text-slate-400 hover:text-indigo-500 hover:bg-white/40 dark:hover:bg-white/5' }}">
                <span class="material-symbols-outlined text-[20px]">campaign</span>
                <span class="font-headline text-sm">Announcements</span>
            </a>
            {{-- Assets: all roles see their own assets; managers/HR see full management --}}
            @role('Manager|HR Admin|Super Admin')
            <a href="{{ route('assets.index') }}" wire:navigate
                class="flex items-center gap-3 px-4 py-2.5 rounded-xl transition-all duration-300 {{ request()->routeIs('assets.index') ? 'bg-white/80 dark:bg-white/10 text-indigo-600 dark:text-indigo-400 font-bold shadow-sm' : 'text-slate-500 dark:text-slate-400 hover:text-indigo-500 hover:bg-white/40 dark:hover:bg-white/5' }}">
                <span class="material-symbols-outlined text-[20px]">inventory_2</span>
                <span class="font-headline text-sm">Assets</span>
            </a>
            @endrole
            @role('Employee')
            <a href="{{ route('assets.my') }}" wire:navigate
                class="flex items-center gap-3 px-4 py-2.5 rounded-xl transition-all duration-300 {{ request()->routeIs('assets.my') ? 'bg-white/80 dark:bg-white/10 text-indigo-600 dark:text-indigo-400 font-bold shadow-sm' : 'text-slate-500 dark:text-slate-400 hover:text-indigo-500 hover:bg-white/40 dark:hover:bg-white/5' }}">
                <span class="material-symbols-outlined text-[20px]">inventory_2</span>
                <span class="font-headline text-sm">My Assets</span>
            </a>
            @endrole
        </div>

        {{-- Accounts --}}
        @can('view accounts')
        <div>
            <p class="px-4 mb-1 text-[10px] font-bold uppercase tracking-widest text-slate-400 dark:text-slate-500">Accounts</p>
            @can('manage accounts')
            <a href="{{ route('accounts.index') }}" wire:navigate
                class="flex items-center gap-3 px-4 py-2.5 rounded-xl transition-all duration-300 {{ request()->routeIs('accounts.index') ? 'bg-white/80 dark:bg-white/10 text-indigo-600 dark:text-indigo-400 font-bold shadow-sm' : 'text-slate-500 dark:text-slate-400 hover:text-indigo-500 hover:bg-white/40 dark:hover:bg-white/5' }}">
                <span class="material-symbols-outlined text-[20px]">account_balance_wallet</span>
                <span class="font-headline text-sm">Fund Accounts</span>
            </a>
            @endcan
            <a href="{{ route('accounts.procurement') }}" wire:navigate
                class="flex items-center gap-3 px-4 py-2.5 rounded-xl transition-all duration-300 {{ request()->routeIs('accounts.procurement') ? 'bg-white/80 dark:bg-white/10 text-indigo-600 dark:text-indigo-400 font-bold shadow-sm' : 'text-slate-500 dark:text-slate-400 hover:text-indigo-500 hover:bg-white/40 dark:hover:bg-white/5' }}">
                <span class="material-symbols-outlined text-[20px]">shopping_cart</span>
                <span class="font-headline text-sm">Procurement</span>
            </a>
            <a href="{{ route('accounts.overtime') }}" wire:navigate
                class="flex items-center gap-3 px-4 py-2.5 rounded-xl transition-all duration-300 {{ request()->routeIs('accounts.overtime') ? 'bg-white/80 dark:bg-white/10 text-indigo-600 dark:text-indigo-400 font-bold shadow-sm' : 'text-slate-500 dark:text-slate-400 hover:text-indigo-500 hover:bg-white/40 dark:hover:bg-white/5' }}">
                <span class="material-symbols-outlined text-[20px]">schedule</span>
                <span class="font-headline text-sm">Overtime</span>
            </a>
            <a href="{{ route('accounts.transport') }}" wire:navigate
                class="flex items-center gap-3 px-4 py-2.5 rounded-xl transition-all duration-300 {{ request()->routeIs('accounts.transport') ? 'bg-white/80 dark:bg-white/10 text-indigo-600 dark:text-indigo-400 font-bold shadow-sm' : 'text-slate-500 dark:text-slate-400 hover:text-indigo-500 hover:bg-white/40 dark:hover:bg-white/5' }}">
                <span class="material-symbols-outlined text-[20px]">directions_car</span>
                <span class="font-headline text-sm">Transport</span>
            </a>
        </div>
        @endcan

        {{-- Payroll --}}
        <div>
            <p class="px-4 mb-1 text-[10px] font-bold uppercase tracking-widest text-slate-400 dark:text-slate-500">Payroll</p>            @role('HR Admin|Super Admin')
            <a href="{{ route('payroll.run') }}" wire:navigate
                class="flex items-center gap-3 px-4 py-2.5 rounded-xl transition-all duration-300 {{ request()->routeIs('payroll.*') ? 'bg-white/80 dark:bg-white/10 text-indigo-600 dark:text-indigo-400 font-bold shadow-sm' : 'text-slate-500 dark:text-slate-400 hover:text-indigo-500 hover:bg-white/40 dark:hover:bg-white/5' }}">
                <span class="material-symbols-outlined text-[20px]">payments</span>
                <span class="font-headline text-sm">Run Payroll</span>
            </a>
            @endrole
            <a href="{{ route('payslips.index') }}" wire:navigate
                class="flex items-center gap-3 px-4 py-2.5 rounded-xl transition-all duration-300 {{ request()->routeIs('payslips.*') ? 'bg-white/80 dark:bg-white/10 text-indigo-600 dark:text-indigo-400 font-bold shadow-sm' : 'text-slate-500 dark:text-slate-400 hover:text-indigo-500 hover:bg-white/40 dark:hover:bg-white/5' }}">
                <span class="material-symbols-outlined text-[20px]">receipt_long</span>
                <span class="font-headline text-sm">Payslips</span>
            </a>
        </div>

        {{-- Admin --}}
        @role('Super Admin')
        <div>
            <p class="px-4 mb-1 text-[10px] font-bold uppercase tracking-widest text-slate-400 dark:text-slate-500">Admin</p>
            <a href="{{ route('admin.audit-logs') }}" wire:navigate
                class="flex items-center gap-3 px-4 py-2.5 rounded-xl transition-all duration-300 {{ request()->routeIs('admin.*') ? 'bg-white/80 dark:bg-white/10 text-indigo-600 dark:text-indigo-400 font-bold shadow-sm' : 'text-slate-500 dark:text-slate-400 hover:text-indigo-500 hover:bg-white/40 dark:hover:bg-white/5' }}">
                <span class="material-symbols-outlined text-[20px]">manage_history</span>
                <span class="font-headline text-sm">Audit Logs</span>
            </a>
        </div>
        @endrole

    </nav>

    <div class="px-4 py-6 space-y-1 border-t border-slate-200/50 dark:border-white/5">
        <a href="{{ route('profile') }}" wire:navigate
            class="flex items-center gap-3 px-4 py-2 text-slate-500 dark:text-slate-400 hover:text-indigo-500 transition-all rounded-xl">
            <span class="material-symbols-outlined">settings</span>
            <span class="text-sm font-headline">Settings</span>
        </a>
        <button wire:click="logout"
            class="w-full flex items-center gap-3 px-4 py-2 text-slate-500 dark:text-slate-400 hover:text-red-500 transition-all rounded-xl">
            <span class="material-symbols-outlined">logout</span>
            <span class="text-sm font-headline">Log Out</span>
        </button>
    </div>
</aside>

<!-- Top Bar -->
<header class="fixed top-0 right-0 left-64 h-16 px-8 flex justify-between items-center z-30 bg-white/60 dark:bg-slate-900/70 backdrop-blur-2xl border-b border-white/40 dark:border-white/5">
    <div class="flex items-center flex-1 max-w-xl">
        <div class="relative w-full">
            <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-sm">search</span>
            <input class="w-full pl-10 pr-4 py-2 bg-surface-container-low dark:bg-slate-800 dark:text-slate-200 dark:placeholder-slate-500 border-none rounded-xl text-sm focus:ring-2 focus:ring-primary/20 transition-all"
                placeholder="Search employees, records..." type="text"/>
        </div>
    </div>

    <div class="flex items-center gap-3 border-l border-slate-200 dark:border-white/10 pl-6 ml-6">

        {{-- Dark mode toggle --}}
        <button @click="dark = !dark"
            class="w-9 h-9 flex items-center justify-center rounded-xl text-slate-500 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors"
            :title="dark ? 'Switch to light mode' : 'Switch to dark mode'">
            <span class="material-symbols-outlined text-sm" x-show="!dark">dark_mode</span>
            <span class="material-symbols-outlined text-sm" x-show="dark" x-cloak>light_mode</span>
        </button>

        {{-- Notifications --}}
        <button class="relative w-9 h-9 flex items-center justify-center rounded-xl text-slate-500 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors"
            onclick="window.location='{{ route('notifications.index') }}'">
            <span class="material-symbols-outlined text-sm">notifications</span>
            @php $unread = auth()->user()->unreadNotificationsCount(); @endphp
            @if($unread > 0)
            <span class="absolute top-1 right-1 w-4 h-4 bg-error rounded-full border-2 border-white dark:border-slate-900 text-white text-[9px] font-bold flex items-center justify-center">{{ $unread > 9 ? '9+' : $unread }}</span>
            @endif
        </button>

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
</div>

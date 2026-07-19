<?php

namespace App\Livewire\Dashboard;

use App\Models\Announcement;
use App\Models\AuditLog;
use App\Models\Department;
use App\Models\LeaveRequest;
use App\Models\PayrollDetail;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class CompanyDashboard extends Component
{
    public function render()
    {
        $user = Auth::user();
        $tenant = $user->tenant;

        // Employee stats
        $totalEmployees = User::where('tenant_id', $user->tenant_id)->count();
        $activeEmployees = User::where('tenant_id', $user->tenant_id)->where('status', 'active')->count();

        // Department breakdown
        $departments = Department::where('tenant_id', $user->tenant_id)
            ->withCount('employees')
            ->orderBy('name')
            ->get();

        // Leave overview
        $pendingLeaves = LeaveRequest::where('tenant_id', $user->tenant_id)
            ->where('status', 'pending')
            ->count();

        $upcomingLeaves = LeaveRequest::where('tenant_id', $user->tenant_id)
            ->where('status', 'approved')
            ->where('start_date', '>=', now()->toDateString())
            ->where('start_date', '<=', now()->addDays(30)->toDateString())
            ->with('user:id,name')
            ->orderBy('start_date')
            ->limit(5)
            ->get();

        // Payroll summary
        $lastPayroll = PayrollDetail::where('tenant_id', $user->tenant_id)
            ->orderByDesc('created_at')
            ->first();

        $pendingPayroll = PayrollDetail::where('tenant_id', $user->tenant_id)
            ->where('status', 'pending')
            ->count();

        $totalPayrollCost = PayrollDetail::where('tenant_id', $user->tenant_id)
            ->where('status', 'paid')
            ->sum('net_salary');

        // Announcements
        $announcements = Announcement::where('tenant_id', $user->tenant_id)
            ->published()
            ->orderByDesc('pinned')
            ->orderByDesc('published_at')
            ->limit(3)
            ->get();

        // Recent activity
        $recentActivity = AuditLog::where('tenant_id', $user->tenant_id)
            ->with('user:id,name')
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();

        // Plan info
        $planConfig = $tenant ? $tenant->getPlanConfig() : [];
        $isOnTrial = $tenant && $tenant->isOnTrial();

        return view('livewire.dashboard.company-dashboard', compact(
            'user', 'tenant', 'totalEmployees', 'activeEmployees',
            'departments', 'pendingLeaves', 'upcomingLeaves',
            'lastPayroll', 'pendingPayroll', 'totalPayrollCost',
            'announcements', 'recentActivity', 'planConfig', 'isOnTrial'
        ));
    }
}

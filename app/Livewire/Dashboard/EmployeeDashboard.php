<?php

namespace App\Livewire\Dashboard;

use App\Models\Announcement;
use App\Models\LeaveRequest;
use App\Models\Payslip;
use App\Models\Task;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class EmployeeDashboard extends Component
{
    public function render()
    {
        $user = Auth::user();

        // Leave
        $pendingLeaves = LeaveRequest::where('user_id', $user->id)->where('status', 'pending')->count();

        // Payslips — only columns needed for display
        $recentPayslips = Payslip::where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->limit(3)
            ->get(['id', 'pay_period', 'net_pay', 'created_at']);

        // Task metrics — single query with conditional aggregation instead of 4 separate counts
        $taskStats = Task::selectRaw("
            SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
            SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress,
            SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
            SUM(CASE WHEN status IN ('pending','in_progress') AND due_date IS NOT NULL AND due_date < ? THEN 1 ELSE 0 END) as overdue
        ", [now()->toDateString()])
            ->where(fn($q) => $q->where('assigned_to', $user->id)->orWhere('created_by', $user->id))
            ->first();

        $taskMetrics = [
            'pending'     => (int) ($taskStats->pending ?? 0),
            'in_progress' => (int) ($taskStats->in_progress ?? 0),
            'completed'   => (int) ($taskStats->completed ?? 0),
            'overdue'     => (int) ($taskStats->overdue ?? 0),
        ];

        $myTasks = Task::where(fn($q) => $q->where('assigned_to', $user->id)->orWhere('created_by', $user->id))
            ->whereIn('status', ['pending', 'in_progress'])
            ->orderByRaw("CASE priority WHEN 'urgent' THEN 1 WHEN 'high' THEN 2 WHEN 'medium' THEN 3 ELSE 4 END")
            ->orderBy('due_date')
            ->limit(6)
            ->get(['id', 'title', 'status', 'priority', 'due_date', 'assigned_to', 'created_by']);

        $myTasksCount = $taskMetrics['pending'] + $taskMetrics['in_progress'];

        $announcements = Announcement::published()->orderByDesc('pinned')->orderByDesc('published_at')->limit(3)->get();

        $teamStats = null;
        if ($user->isManager()) {
            $subordinateIds = $user->subordinates()->pluck('id');
            if ($subordinateIds->isNotEmpty()) {
                $teamTaskStats = Task::selectRaw("
                    SUM(CASE WHEN status IN ('pending','in_progress') THEN 1 ELSE 0 END) as open_tasks,
                    SUM(CASE WHEN status IN ('pending','in_progress') AND due_date IS NOT NULL AND due_date < ? THEN 1 ELSE 0 END) as overdue_tasks
                ", [now()->toDateString()])
                    ->whereIn('assigned_to', $subordinateIds)
                    ->first();

                $teamStats = [
                    'total_members'     => $subordinateIds->count(),
                    'pending_approvals' => LeaveRequest::whereIn('user_id', $subordinateIds)->where('status', 'pending')->count(),
                    'open_tasks'        => (int) ($teamTaskStats->open_tasks ?? 0),
                    'overdue_tasks'     => (int) ($teamTaskStats->overdue_tasks ?? 0),
                ];
            }
        }

        return view('livewire.dashboard.employee-dashboard', compact(
            'pendingLeaves', 'recentPayslips',
            'taskMetrics', 'myTasks', 'myTasksCount',
            'announcements', 'teamStats'
        ));
    }
}

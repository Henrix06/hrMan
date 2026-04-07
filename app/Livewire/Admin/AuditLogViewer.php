<?php

namespace App\Livewire\Admin;

use App\Models\AuditLog;
use Livewire\Component;
use Livewire\WithPagination;

class AuditLogViewer extends Component
{
    use WithPagination;

    public string $search = '';
    public string $actionFilter = '';

    public function render()
    {
        $this->authorize('view audit logs');

        $logs = AuditLog::with('user:id,name')
            ->select(['id', 'user_id', 'action', 'description', 'ip_address', 'created_at'])
            ->when($this->search, fn($q) => $q->where('description', 'like', "%{$this->search}%"))
            ->when($this->actionFilter, fn($q) => $q->where('action', $this->actionFilter))
            ->orderByDesc('created_at')
            ->paginate(25);

        $actions = \Illuminate\Support\Facades\Cache::remember('audit_log_actions', 300,
            fn() => AuditLog::distinct()->orderBy('action')->pluck('action')
        );

        return view('livewire.admin.audit-logs', compact('logs', 'actions'));
    }
}

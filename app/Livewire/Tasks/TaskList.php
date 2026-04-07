<?php

namespace App\Livewire\Tasks;

use App\Models\AuditLog;
use App\Models\Department;
use App\Models\HrNotification;
use App\Models\Task;
use App\Models\User;
use Livewire\Component;
use Livewire\WithPagination;

class TaskList extends Component
{
    use WithPagination;

    public string $search = '';
    public string $statusFilter = '';
    public string $priorityFilter = '';
    public bool $myTasksOnly = false;

    public bool $showModal = false;
    public bool $editing = false;
    public ?int $editingId = null;

    public string $title = '';
    public string $description = '';
    public ?int $assigned_to = null;
    public ?int $department_id = null;
    public string $status = 'pending';
    public string $priority = 'medium';
    public string $due_date = '';
    public string $visibility = 'team';

    // Comment
    public bool $showTask = false;
    public ?Task $activeTask = null;
    public string $commentBody = '';

    protected function rules(): array
    {
        $user = auth()->user();
        $isEmployee = !$user->isManager() && !$user->isHRAdmin();

        return [
            'title'         => 'required|string|max:200',
            'description'   => 'nullable|string|max:2000',
            'assigned_to'   => $isEmployee ? 'nullable' : 'nullable|exists:users,id',
            'department_id' => $isEmployee ? 'nullable' : 'nullable|exists:departments,id',
            'status'        => 'required|in:pending,in_progress,completed,cancelled',
            'priority'      => 'required|in:low,medium,high,urgent',
            'due_date'      => 'nullable|date',
            'visibility'    => 'required|in:team,private',
        ];
    }

    public function openCreate(): void
    {
        $this->reset(['title', 'description', 'assigned_to', 'department_id', 'due_date', 'editingId', 'editing']);
        $this->status = 'pending';
        $this->priority = 'medium';
        // Employees default to private; managers/HR default to team
        $this->visibility = auth()->user()->isManager() || auth()->user()->isHRAdmin() ? 'team' : 'private';
        $this->showModal = true;
    }

    public function openEdit(int $id): void
    {
        $task = Task::findOrFail($id);

        // Only creator or HR/Admin can edit
        if ($task->created_by !== auth()->id() && !auth()->user()->isHRAdmin()) {
            session()->flash('error', 'You can only edit tasks you created.');
            return;
        }

        $this->editingId = $id;
        $this->editing = true;
        $this->title = $task->title;
        $this->description = $task->description ?? '';
        $this->assigned_to = $task->assigned_to;
        $this->department_id = $task->department_id;
        $this->status = $task->status;
        $this->priority = $task->priority;
        $this->visibility = $task->visibility;
        $this->due_date = $task->due_date?->format('Y-m-d') ?? '';
        $this->showModal = true;
    }

    public function save(): void
    {
        $user = auth()->user();
        $isEmployee = !$user->isManager() && !$user->isHRAdmin();

        $data = $this->validate();
        $data['due_date'] = $data['due_date'] ?: null;

        // Employees can only assign tasks to themselves
        if ($isEmployee) {
            $data['assigned_to'] = $user->id;
            $data['department_id'] = null;
        }

        if ($this->editing && $this->editingId) {
            $task = Task::findOrFail($this->editingId);

            if ($task->created_by !== $user->id && !$user->isHRAdmin()) {
                session()->flash('error', 'You can only edit tasks you created.');
                return;
            }

            $old = $task->toArray();
            if ($data['status'] === 'completed' && $task->status !== 'completed') {
                $data['completed_at'] = now();
            }
            $task->update($data);
            AuditLog::record('update', "Updated task: {$task->title}", $task, $old, $task->fresh()->toArray());
            session()->flash('message', 'Task updated.');
        } else {
            $data['created_by'] = $user->id;
            $task = Task::create($data);
            AuditLog::record('create', "Created task: {$task->title}", $task);

            if ($task->assigned_to && $task->assigned_to !== $user->id) {
                HrNotification::notify(
                    $task->assigned_to,
                    'task_assigned',
                    'New Task Assigned',
                    "You have been assigned: {$task->title}",
                    route('tasks.index')
                );
            }

            // Notify manager when employee creates a private task
            if ($isEmployee && $task->visibility === 'private' && $user->manager_id) {
                HrNotification::notify(
                    $user->manager_id,
                    'task_assigned',
                    'Employee Created a Task',
                    "{$user->name} created a task: {$task->title}",
                    route('tasks.index')
                );
            }

            session()->flash('message', 'Task created.');
        }

        $this->showModal = false;
        $this->resetPage();
    }

    public function updateStatus(int $id, string $status): void
    {
        $task = Task::findOrFail($id);
        $this->authorize('update', $task);
        $old = ['status' => $task->status];
        $task->status = $status;
        if ($status === 'completed') {
            $task->completed_at = now();
        }
        $task->save();
        AuditLog::record('update', "Changed task status to {$status}: {$task->title}", $task, $old, ['status' => $status]);
    }

    public function viewTask(int $id): void
    {
        $this->activeTask = Task::with([
            'assignee:id,name',
            'creator:id,name',
            'comments' => fn($q) => $q->latest()->limit(50),
            'comments.user:id,name',
        ])->findOrFail($id);
        $this->commentBody = '';
        $this->showTask = true;
    }

    public function addComment(): void
    {
        $this->validate(['commentBody' => 'required|string|max:1000']);
        $this->activeTask->comments()->create([
            'user_id' => auth()->id(),
            'body' => $this->commentBody,
        ]);
        $this->commentBody = '';
        $this->activeTask->load(['comments' => fn($q) => $q->latest()->limit(50), 'comments.user:id,name']);

        // Notify task creator/assignee
        $notifyIds = collect([$this->activeTask->created_by, $this->activeTask->assigned_to])
            ->filter(fn($id) => $id && $id !== auth()->id())
            ->unique();

        foreach ($notifyIds as $uid) {
            HrNotification::notify($uid, 'task_comment', 'New Comment on Task',
                auth()->user()->name . ' commented on: ' . $this->activeTask->title,
                route('tasks.index'));
        }
    }

    public function deleteComment(int $commentId): void
    {
        $comment = \App\Models\TaskComment::findOrFail($commentId);
        if ($comment->user_id !== auth()->id() && !auth()->user()->isHRAdmin()) {
            abort(403);
        }
        $comment->delete();
        $this->activeTask->load(['comments' => fn($q) => $q->latest()->limit(50), 'comments.user:id,name']);
    }

    public function delete(int $id): void
    {
        $this->authorize('manage settings');
        $task = Task::findOrFail($id);
        AuditLog::record('delete', "Deleted task: {$task->title}", $task);
        $task->delete();
        session()->flash('message', 'Task deleted.');
    }

    public function render()
    {
        $user = auth()->user();

        $query = Task::with(['assignee:id,name', 'creator:id,name', 'department:id,name'])
            ->withCount('comments')
            ->when($this->search, fn($q) => $q->where('title', 'like', "%{$this->search}%"))
            ->when($this->statusFilter, fn($q) => $q->where('status', $this->statusFilter))
            ->when($this->priorityFilter, fn($q) => $q->where('priority', $this->priorityFilter))
            ->when($this->myTasksOnly, fn($q) => $q->where('assigned_to', $user->id))
            ->unless($user->isHRAdmin(), function ($q) use ($user) {
                if ($user->isManager()) {
                    $subordinateIds = $user->subordinates()->pluck('id')->push($user->id);
                    // Managers see: their own tasks + subordinate team tasks + subordinate private tasks (read-only)
                    $q->where(function ($sq) use ($subordinateIds, $user) {
                        $sq->whereIn('created_by', $subordinateIds)
                           ->orWhereIn('assigned_to', $subordinateIds)
                           ->orWhere('created_by', $user->id);
                    });
                } else {
                    // Employees see only their own created/assigned tasks
                    $q->where(fn($sq) => $sq->where('assigned_to', $user->id)->orWhere('created_by', $user->id));
                }
            })
            ->orderByRaw("CASE priority WHEN 'urgent' THEN 1 WHEN 'high' THEN 2 WHEN 'medium' THEN 3 ELSE 4 END")
            ->orderByDesc('created_at');

        $isEmployee = !$user->isManager() && !$user->isHRAdmin();
        $employees = $user->isHRAdmin() || $user->isManager()
            ? User::orderBy('name')->get(['id', 'name', 'position'])
            : collect([$user]);
        $departments = Department::where('active', true)->orderBy('name')->get();

        return view('livewire.tasks.index', [
            'tasks' => $query->paginate(15),
            'employees' => $employees,
            'departments' => $departments,
            'isEmployee' => $isEmployee,
        ]);
    }
}

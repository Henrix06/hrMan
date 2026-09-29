<?php

namespace App\Livewire\Tasks;

use App\Exceptions\Task\TaskCollaborationException;
use App\Models\AuditLog;
use App\Models\Department;
use App\Models\HrNotification;
use App\Models\Task;
use App\Models\TaskActivity;
use App\Models\TaskAttachment;
use App\Models\TaskChecklistItem;
use App\Models\TaskComment;
use App\Models\User;
use App\Services\TaskCollaborationService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

class TaskList extends Component
{
    use WithFileUploads;
    use WithPagination;

    public string $search = '';

    public string $statusFilter = '';

    public string $priorityFilter = '';

    public string $labelFilter = '';

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

    // Task drawer
    public bool $showTask = false;

    public ?Task $activeTask = null;

    public string $commentBody = '';

    // Collaboration
    public string $checklistInput = '';

    public string $labelInput = '';

    public string $watcherToAdd = '';

    public $attachmentUpload = null;

    protected function rules(): array
    {
        $user = auth()->user();
        $isEmployee = ! $user->isManager() && ! $user->isHRAdmin();

        return [
            'title' => 'required|string|max:200',
            'description' => 'nullable|string|max:2000',
            'assigned_to' => $isEmployee ? 'nullable' : 'nullable|exists:users,id',
            'department_id' => $isEmployee ? 'nullable' : 'nullable|exists:departments,id',
            'status' => 'required|in:pending,in_progress,completed,cancelled',
            'priority' => 'required|in:low,medium,high,urgent',
            'due_date' => 'nullable|date',
            'visibility' => 'required|in:team,private',
        ];
    }

    /* ---------------------------------------------------------------------
     |  Create / edit
     * ------------------------------------------------------------------- */

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
        if ($task->created_by !== auth()->id() && ! auth()->user()->isHRAdmin()) {
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
        $isEmployee = ! $user->isManager() && ! $user->isHRAdmin();
        $collaboration = app(TaskCollaborationService::class);

        $data = $this->validate();
        $data['due_date'] = $data['due_date'] ?: null;

        // Employees can only assign tasks to themselves
        if ($isEmployee) {
            $data['assigned_to'] = $user->id;
            $data['department_id'] = null;
        }

        if ($this->editing && $this->editingId) {
            $task = Task::findOrFail($this->editingId);

            if ($task->created_by !== $user->id && ! $user->isHRAdmin()) {
                session()->flash('error', 'You can only edit tasks you created.');

                return;
            }

            $old = $task->toArray();
            $previousStatus = $task->status;
            $previousAssignee = $task->assigned_to;

            if ($data['status'] === 'completed' && $task->status !== 'completed') {
                $data['completed_at'] = now();
            }

            $task->update($data);

            AuditLog::record('update', "Updated task: {$task->title}", $task, $old, $task->fresh()->toArray());

            if ($task->wasChanged('status')) {
                $collaboration->recordStatusChange($task, $previousStatus, $task->status, $user);
            }

            if ($task->wasChanged('assigned_to')) {
                $collaboration->recordAssignment($task, $previousAssignee, $task->assigned_to, $user);

                if ($task->assigned_to && $task->assigned_to !== $user->id) {
                    HrNotification::notify(
                        $task->assigned_to,
                        'task_assigned',
                        'Task Assigned to You',
                        "You have been assigned: {$task->title}",
                        route('tasks.index')
                    );
                }
            }

            $changed = [];

            foreach (['title' => 'title', 'description' => 'description', 'priority' => 'priority', 'due_date' => 'due date', 'visibility' => 'visibility', 'department_id' => 'department'] as $field => $label) {
                if ($task->wasChanged($field)) {
                    $changed[] = $label;
                }
            }

            $collaboration->recordChanges($task, $changed, $user);

            session()->flash('message', 'Task updated.');
        } else {
            $data['created_by'] = $user->id;
            $task = Task::create($data);
            AuditLog::record('create', "Created task: {$task->title}", $task);
            $collaboration->recordCreated($task, $user);

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

        if ($task->status === $status) {
            return;
        }

        $old = ['status' => $task->status];
        $previous = $task->status;

        $task->status = $status;

        if ($status === 'completed') {
            $task->completed_at = now();
        }

        $task->save();

        AuditLog::record('update', "Changed task status to {$status}: {$task->title}", $task, $old, ['status' => $status]);

        $collaboration = app(TaskCollaborationService::class);
        $collaboration->recordStatusChange($task, $previous, $status, auth()->user());
        $collaboration->notifyWatchers(
            $task,
            'task_status',
            'Task Status Updated',
            auth()->user()->name.' moved "'.$task->title.'" to '.str_replace('_', ' ', $status),
            [auth()->id()],
        );

        if ($this->activeTask && $this->activeTask->id === $task->id) {
            $this->refreshTask();
        }
    }

    /* ---------------------------------------------------------------------
     |  Task drawer
     * ------------------------------------------------------------------- */

    public function viewTask(int $id): void
    {
        $task = Task::findOrFail($id);
        $this->authorize('view', $task);

        $this->activeTask = $task;
        $this->reset(['commentBody', 'checklistInput', 'labelInput', 'watcherToAdd', 'attachmentUpload']);
        $this->resetErrorBag();
        $this->refreshTask();
        $this->showTask = true;
    }

    private function activeTask(): Task
    {
        abort_if($this->activeTask === null, 404);

        return $this->activeTask;
    }

    private function refreshTask(): void
    {
        if ($this->activeTask === null) {
            return;
        }

        $this->activeTask = Task::with([
            'assignee:id,name',
            'creator:id,name',
            'comments' => fn ($q) => $q->latest()->limit(50),
            'comments.user:id,name',
            'checklistItems',
            'attachments' => fn ($q) => $q->latest(),
            'attachments.uploader:id,name',
            'watchers.user:id,name',
            'activities' => fn ($q) => $q->latest()->limit(50),
            'activities.user:id,name',
        ])->findOrFail($this->activeTask->id);
    }

    /**
     * Comments and system events interleaved, newest first.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function timeline(): Collection
    {
        if ($this->activeTask === null) {
            return collect();
        }

        $comments = $this->activeTask->comments->map(fn (TaskComment $comment) => [
            'kind' => 'comment',
            'id' => $comment->id,
            'user' => $comment->user,
            'body' => $comment->body,
            'created_at' => $comment->created_at,
        ]);

        $events = $this->activeTask->activities->map(fn (TaskActivity $activity) => [
            'kind' => 'activity',
            'type' => $activity->type,
            'icon' => $activity->icon(),
            'user' => $activity->user,
            'description' => $activity->description,
            'created_at' => $activity->created_at,
        ]);

        return $comments->concat($events)->sortByDesc('created_at')->values();
    }

    /* ---------------------------------------------------------------------
     |  Comments and @mentions
     * ------------------------------------------------------------------- */

    public function addComment(): void
    {
        $task = $this->activeTask();
        $this->authorize('view', $task);
        $this->validate(['commentBody' => 'required|string|max:1000']);

        app(TaskCollaborationService::class)->addComment($task, $this->commentBody, auth()->user());

        $this->commentBody = '';
        $this->refreshTask();
    }

    public function deleteComment(int $commentId): void
    {
        $task = $this->activeTask();
        $this->authorize('view', $task);

        $comment = TaskComment::where('task_id', $task->id)->findOrFail($commentId);

        if ($comment->user_id !== auth()->id() && ! auth()->user()->isHRAdmin()) {
            abort(403);
        }

        $comment->delete();
        $this->refreshTask();
    }

    /* ---------------------------------------------------------------------
     |  Watchers
     * ------------------------------------------------------------------- */

    public function toggleWatch(): void
    {
        $task = $this->activeTask();
        $this->authorize('view', $task);

        $watching = app(TaskCollaborationService::class)->toggleWatch($task, auth()->user());

        session()->flash('message', $watching ? 'You are now watching this task.' : 'You stopped watching this task.');
        $this->refreshTask();
    }

    public function addWatcher(): void
    {
        $task = $this->activeTask();
        $this->authorize('update', $task);
        $this->validate(['watcherToAdd' => 'required|integer']);

        $user = User::where('tenant_id', $task->tenant_id)->findOrFail((int) $this->watcherToAdd);

        app(TaskCollaborationService::class)->watch($task, $user);

        $this->watcherToAdd = '';
        $this->refreshTask();
    }

    public function removeWatcher(int $userId): void
    {
        $task = $this->activeTask();

        if ($userId === auth()->id()) {
            $this->authorize('view', $task);
        } else {
            $this->authorize('update', $task);
        }

        $user = User::where('tenant_id', $task->tenant_id)->findOrFail($userId);

        app(TaskCollaborationService::class)->unwatch($task, $user);

        $this->refreshTask();
    }

    /* ---------------------------------------------------------------------
     |  Labels
     * ------------------------------------------------------------------- */

    public function addLabel(): void
    {
        $task = $this->activeTask();
        $this->authorize('update', $task);
        $this->validate(['labelInput' => 'required|string|max:200']);

        $service = app(TaskCollaborationService::class);
        $firstError = null;

        foreach (explode(',', $this->labelInput) as $raw) {
            $label = trim($raw);

            if ($label === '') {
                continue;
            }

            try {
                $service->addLabel($task, $label, auth()->user());
            } catch (TaskCollaborationException $e) {
                $firstError ??= $e->getMessage();
            }
        }

        if ($firstError !== null) {
            $this->addError('labelInput', $firstError);
        }

        $this->labelInput = '';
        $this->refreshTask();
    }

    public function removeLabel(string $label): void
    {
        $task = $this->activeTask();
        $this->authorize('update', $task);

        app(TaskCollaborationService::class)->removeLabel($task, $label, auth()->user());

        $this->refreshTask();
    }

    /* ---------------------------------------------------------------------
     |  Checklist
     * ------------------------------------------------------------------- */

    public function addChecklistItem(): void
    {
        $task = $this->activeTask();
        $this->authorize('view', $task);
        $this->validate(['checklistInput' => 'required|string|max:200']);

        app(TaskCollaborationService::class)->addChecklistItem($task, $this->checklistInput, auth()->user());

        $this->checklistInput = '';
        $this->refreshTask();
    }

    public function toggleChecklistItem(int $itemId): void
    {
        $item = $this->checklistItem($itemId);
        $this->authorize('view', $item->task);

        app(TaskCollaborationService::class)->toggleChecklistItem($item, auth()->user());

        $this->refreshTask();
    }

    public function removeChecklistItem(int $itemId): void
    {
        $item = $this->checklistItem($itemId);
        $this->authorize('view', $item->task);

        app(TaskCollaborationService::class)->removeChecklistItem($item, auth()->user());

        $this->refreshTask();
    }

    private function checklistItem(int $itemId): TaskChecklistItem
    {
        $task = $this->activeTask();

        return TaskChecklistItem::where('task_id', $task->id)->findOrFail($itemId);
    }

    /* ---------------------------------------------------------------------
     |  Attachments
     * ------------------------------------------------------------------- */

    public function uploadAttachment(): void
    {
        $task = $this->activeTask();
        $this->authorize('view', $task);

        $this->validate([
            'attachmentUpload' => 'required|file|max:'.(int) config('tasks.max_attachment_kb', 10240)
                .'|mimes:'.implode(',', config('tasks.allowed_attachment_extensions', [])),
        ]);

        try {
            app(TaskCollaborationService::class)->attach($task, $this->attachmentUpload, auth()->user());
            session()->flash('message', 'Attachment uploaded.');
        } catch (TaskCollaborationException $e) {
            $this->addError('attachmentUpload', $e->getMessage());
        }

        $this->attachmentUpload = null;
        $this->refreshTask();
    }

    public function removeAttachment(int $attachmentId): void
    {
        $task = $this->activeTask();
        $this->authorize('view', $task);

        $attachment = TaskAttachment::where('task_id', $task->id)->findOrFail($attachmentId);
        $user = auth()->user();

        if ($attachment->user_id !== $user->id && $task->created_by !== $user->id && ! $user->isHRAdmin()) {
            abort(403);
        }

        app(TaskCollaborationService::class)->removeAttachment($attachment, $user);

        $this->refreshTask();
    }

    /* ---------------------------------------------------------------------
     |  Delete
     * ------------------------------------------------------------------- */

    public function delete(int $id): void
    {
        $this->authorize('manage settings');
        $task = Task::findOrFail($id);
        AuditLog::record('delete', "Deleted task: {$task->title}", $task);
        $task->delete();

        if ($this->activeTask && $this->activeTask->id === $id) {
            $this->showTask = false;
            $this->activeTask = null;
        }

        session()->flash('message', 'Task deleted.');
    }

    /* ---------------------------------------------------------------------
     |  Listing
     * ------------------------------------------------------------------- */

    /**
     * Every task the signed-in user is allowed to see, before filters.
     */
    private function visibleTasksQuery(): Builder
    {
        $user = auth()->user();

        return Task::query()->unless($user->isHRAdmin(), function ($q) use ($user) {
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
                $q->where(fn ($sq) => $sq->where('assigned_to', $user->id)->orWhere('created_by', $user->id));
            }
        });
    }

    public function render()
    {
        $user = auth()->user();

        $query = $this->visibleTasksQuery()
            ->with(['assignee:id,name', 'creator:id,name', 'department:id,name'])
            ->withCount([
                'comments',
                'checklistItems',
                'checklistItems as completed_checklist_items_count' => fn ($q) => $q->where('is_completed', true),
                'attachments',
                'watchers',
            ])
            ->when($this->search, fn ($q) => $q->where('title', 'like', "%{$this->search}%"))
            ->when($this->statusFilter, fn ($q) => $q->where('status', $this->statusFilter))
            ->when($this->priorityFilter, fn ($q) => $q->where('priority', $this->priorityFilter))
            ->when($this->labelFilter, fn ($q) => $q->whereJsonContains('labels', $this->labelFilter))
            ->when($this->myTasksOnly, fn ($q) => $q->where('assigned_to', $user->id))
            ->orderByRaw("CASE priority WHEN 'urgent' THEN 1 WHEN 'high' THEN 2 WHEN 'medium' THEN 3 ELSE 4 END")
            ->orderByDesc('created_at');

        $isEmployee = ! $user->isManager() && ! $user->isHRAdmin();
        $employees = $user->isHRAdmin() || $user->isManager()
            ? User::orderBy('name')->get(['id', 'name', 'position'])
            : collect([$user]);
        $departments = Department::where('active', true)->orderBy('name')->get();

        $availableLabels = $this->visibleTasksQuery()
            ->whereNotNull('labels')
            ->pluck('labels')
            ->flatMap(fn ($labels) => (array) $labels)
            ->unique(fn ($label) => strtolower($label))
            ->sort()
            ->values();

        return view('livewire.tasks.index', [
            'tasks' => $query->paginate(15),
            'employees' => $employees,
            'departments' => $departments,
            'isEmployee' => $isEmployee,
            'availableLabels' => $availableLabels,
        ]);
    }
}

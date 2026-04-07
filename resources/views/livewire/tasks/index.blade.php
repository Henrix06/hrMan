<div>
    @if (session()->has('message'))
        <div class="mb-6 px-4 py-3 rounded-xl bg-green-50 border border-green-200 text-green-700 text-sm">{{ session('message') }}</div>
    @endif
    @if (session()->has('error'))
        <div class="mb-6 px-4 py-3 rounded-xl bg-red-50 border border-red-200 text-red-700 text-sm">{{ session('error') }}</div>
    @endif

    <div class="flex items-center justify-between mb-8">
        <div>
            <h2 class="text-3xl font-extrabold text-slate-900 font-headline">Tasks</h2>
            <p class="text-slate-500 text-sm mt-1">
                @if($isEmployee) Your personal tasks and assignments @else Track and manage work assignments @endif
            </p>
        </div>
        {{-- All users can create tasks --}}
        <button wire:click="openCreate" class="bg-gradient-to-br from-primary to-primary-container text-on-primary px-5 py-2.5 rounded-xl font-bold font-headline shadow-lg shadow-primary/20 hover:opacity-90 transition-opacity flex items-center gap-2">
            <span class="material-symbols-outlined text-sm">add</span> New Task
        </button>
    </div>

    <!-- Filters -->
    <div class="glass-card rounded-[1.5rem] p-4 mb-6 flex flex-wrap gap-3 items-center">
        <input wire:model.live.debounce.300ms="search" type="text" placeholder="Search tasks..."
            class="px-4 py-2 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-primary/20 focus:outline-none flex-1 min-w-48"/>
        <select wire:model.live="statusFilter" class="px-4 py-2 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-primary/20 focus:outline-none">
            <option value="">All Statuses</option>
            <option value="pending">Pending</option>
            <option value="in_progress">In Progress</option>
            <option value="completed">Completed</option>
            <option value="cancelled">Cancelled</option>
        </select>
        <select wire:model.live="priorityFilter" class="px-4 py-2 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-primary/20 focus:outline-none">
            <option value="">All Priorities</option>
            <option value="urgent">Urgent</option>
            <option value="high">High</option>
            <option value="medium">Medium</option>
            <option value="low">Low</option>
        </select>
        @if(!$isEmployee)
        <label class="flex items-center gap-2 text-sm font-semibold text-slate-600 cursor-pointer">
            <input wire:model.live="myTasksOnly" type="checkbox" class="rounded"/> My Tasks
        </label>
        @endif
    </div>

    <!-- Task List -->
    <div class="space-y-3">
        @forelse($tasks as $task)
        @php
            $priorityColors = ['urgent'=>'bg-red-100 text-red-700','high'=>'bg-orange-100 text-orange-700','medium'=>'bg-amber-100 text-amber-700','low'=>'bg-slate-100 text-slate-600'];
            $statusColors = ['pending'=>'bg-amber-100 text-amber-700','in_progress'=>'bg-blue-100 text-blue-700','completed'=>'bg-green-100 text-green-700','cancelled'=>'bg-slate-100 text-slate-500'];
            $isOwner = $task->created_by === auth()->id();
            $isReadOnly = !$isOwner && !auth()->user()->isHRAdmin();
            $isPrivate = $task->visibility === 'private';
        @endphp
        <div class="glass-card rounded-[1.5rem] p-5 flex items-center gap-4 hover:shadow-md transition-shadow {{ $isPrivate && !$isOwner ? 'border-l-4 border-slate-300' : '' }}">
            <div class="flex-1 min-w-0 cursor-pointer" wire:click="viewTask({{ $task->id }})">
                <div class="flex items-center gap-2 mb-1">
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-widest {{ $priorityColors[$task->priority] }}">{{ $task->priority }}</span>
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-widest {{ $statusColors[$task->status] }}">{{ str_replace('_',' ',$task->status) }}</span>
                    @if($isPrivate)
                    <span class="flex items-center gap-0.5 px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-widest bg-slate-100 text-slate-500">
                        <span class="material-symbols-outlined text-[11px]">lock</span> Private
                    </span>
                    @endif
                    @if($task->isOverdue())
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-widest bg-red-100 text-red-700">Overdue</span>
                    @endif
                    @if($isReadOnly && !$isEmployee)
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-widest bg-slate-100 text-slate-400">Read-only</span>
                    @endif
                </div>
                <p class="font-bold text-slate-800 truncate">{{ $task->title }}</p>
                <p class="text-xs text-slate-400 mt-0.5">
                    By {{ $task->creator->name }}
                    @if($task->assignee && $task->assignee->id !== $task->creator->id) &middot; Assigned to {{ $task->assignee->name }} @endif
                    @if($task->due_date) &middot; Due {{ $task->due_date->format('M d, Y') }} @endif
                </p>
            </div>
            <div class="flex items-center gap-2 flex-shrink-0">
                {{-- Status change: owner or assignee can update their own task status --}}
                @if($task->status !== 'completed' && $task->status !== 'cancelled' && ($isOwner || $task->assigned_to === auth()->id()))
                <select wire:change="updateStatus({{ $task->id }}, $event.target.value)"
                    class="text-xs px-3 py-1.5 rounded-xl border border-slate-200 focus:ring-2 focus:ring-primary/20 focus:outline-none">
                    <option value="pending" {{ $task->status==='pending'?'selected':'' }}>Pending</option>
                    <option value="in_progress" {{ $task->status==='in_progress'?'selected':'' }}>In Progress</option>
                    <option value="completed" {{ $task->status==='completed'?'selected':'' }}>Completed</option>
                    <option value="cancelled" {{ $task->status==='cancelled'?'selected':'' }}>Cancelled</option>
                </select>
                @endif
                {{-- Edit: only task creator or HR/Admin --}}
                @if($isOwner || auth()->user()->isHRAdmin())
                <button wire:click="openEdit({{ $task->id }})" class="text-slate-400 hover:text-primary transition-colors" title="Edit task">
                    <span class="material-symbols-outlined text-sm">edit</span>
                </button>
                @endif
                {{-- Delete: HR/Admin only --}}
                @if(auth()->user()->isHRAdmin())
                <button wire:click="delete({{ $task->id }})" wire:confirm="Delete this task?" class="text-slate-400 hover:text-error transition-colors">
                    <span class="material-symbols-outlined text-sm">delete</span>
                </button>
                @endif
            </div>
        </div>
        @empty
        <div class="glass-card rounded-[2rem] py-20 text-center text-slate-400">
            <span class="material-symbols-outlined text-5xl block mb-3">task_alt</span>
            <p>No tasks found.</p>
        </div>
        @endforelse
    </div>

    <div class="mt-4">{{ $tasks->links() }}</div>

    <!-- Create/Edit Modal -->
    @if($showModal)
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm">
        <div class="bg-white rounded-[2rem] shadow-2xl w-full max-w-lg mx-4 p-8 max-h-[90vh] overflow-y-auto">
            <h3 class="text-2xl font-bold font-headline text-slate-900 mb-6">{{ $editing ? 'Edit' : 'New' }} Task</h3>
            <form wire:submit="save" class="space-y-4">
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1">Title *</label>
                    <input wire:model="title" type="text" class="w-full px-4 py-2 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-primary/20 focus:outline-none"/>
                    @error('title') <p class="text-error text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1">Description</label>
                    <textarea wire:model="description" rows="3" class="w-full px-4 py-2 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-primary/20 focus:outline-none"></textarea>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1">Priority</label>
                        <select wire:model="priority" class="w-full px-4 py-2 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-primary/20 focus:outline-none">
                            <option value="low">Low</option>
                            <option value="medium">Medium</option>
                            <option value="high">High</option>
                            <option value="urgent">Urgent</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1">Status</label>
                        <select wire:model="status" class="w-full px-4 py-2 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-primary/20 focus:outline-none">
                            <option value="pending">Pending</option>
                            <option value="in_progress">In Progress</option>
                            <option value="completed">Completed</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                    </div>
                </div>

                {{-- Visibility toggle — available to all --}}
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-2">Visibility</label>
                    <div class="flex gap-3">
                        <label class="flex-1 flex items-center gap-3 p-3 rounded-xl border-2 cursor-pointer transition-colors {{ $visibility === 'team' ? 'border-primary bg-primary/5' : 'border-slate-200' }}">
                            <input wire:model.live="visibility" type="radio" value="team" class="sr-only"/>
                            <span class="material-symbols-outlined text-sm {{ $visibility === 'team' ? 'text-primary' : 'text-slate-400' }}">groups</span>
                            <div>
                                <p class="text-sm font-bold {{ $visibility === 'team' ? 'text-primary' : 'text-slate-700' }}">Team</p>
                                <p class="text-xs text-slate-400">Visible to managers & HR</p>
                            </div>
                        </label>
                        <label class="flex-1 flex items-center gap-3 p-3 rounded-xl border-2 cursor-pointer transition-colors {{ $visibility === 'private' ? 'border-primary bg-primary/5' : 'border-slate-200' }}">
                            <input wire:model.live="visibility" type="radio" value="private" class="sr-only"/>
                            <span class="material-symbols-outlined text-sm {{ $visibility === 'private' ? 'text-primary' : 'text-slate-400' }}">lock</span>
                            <div>
                                <p class="text-sm font-bold {{ $visibility === 'private' ? 'text-primary' : 'text-slate-700' }}">Private</p>
                                <p class="text-xs text-slate-400">You + your manager (read-only)</p>
                            </div>
                        </label>
                    </div>
                </div>

                {{-- Assign/Department: managers and HR only --}}
                @if(!$isEmployee)
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1">Assign To</label>
                    <select wire:model="assigned_to" class="w-full px-4 py-2 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-primary/20 focus:outline-none">
                        <option value="">— Unassigned —</option>
                        @foreach($employees as $emp)
                        <option value="{{ $emp->id }}">{{ $emp->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1">Department</label>
                    <select wire:model="department_id" class="w-full px-4 py-2 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-primary/20 focus:outline-none">
                        <option value="">— None —</option>
                        @foreach($departments as $dept)
                        <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                        @endforeach
                    </select>
                </div>
                @endif

                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1">Due Date</label>
                    <input wire:model="due_date" type="date" class="w-full px-4 py-2 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-primary/20 focus:outline-none"/>
                </div>
                <div class="flex gap-3 pt-2">
                    <button type="submit" class="flex-1 bg-primary text-on-primary py-2.5 rounded-xl font-bold font-headline hover:opacity-90 transition-opacity">
                        {{ $editing ? 'Update' : 'Create' }}
                    </button>
                    <button type="button" wire:click="$set('showModal', false)" class="flex-1 bg-slate-100 text-slate-700 py-2.5 rounded-xl font-bold font-headline hover:bg-slate-200 transition-colors">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>
    @endif

    <!-- Task Detail / Comments Modal -->
    @if($showTask && $activeTask)
    @php $taskIsReadOnly = $activeTask->created_by !== auth()->id() && !auth()->user()->isHRAdmin(); @endphp
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm">
        <div class="bg-white rounded-[2rem] shadow-2xl w-full max-w-2xl mx-4 p-8 max-h-[90vh] overflow-y-auto">
            <div class="flex items-start justify-between mb-4">
                <div>
                    <div class="flex items-center gap-2 mb-1">
                        <h3 class="text-2xl font-bold font-headline text-slate-900">{{ $activeTask->title }}</h3>
                        @if($activeTask->visibility === 'private')
                        <span class="flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-500">
                            <span class="material-symbols-outlined text-[11px]">lock</span> Private
                        </span>
                        @endif
                        @if($taskIsReadOnly)
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-600">Read-only</span>
                        @endif
                    </div>
                    <p class="text-sm text-slate-500">
                        Created by {{ $activeTask->creator->name }}
                        @if($activeTask->assignee) &middot; Assigned to {{ $activeTask->assignee->name }} @endif
                    </p>
                </div>
                <button wire:click="$set('showTask', false)" class="text-slate-400 hover:text-slate-700">
                    <span class="material-symbols-outlined">close</span>
                </button>
            </div>

            @if($activeTask->description)
            <p class="text-slate-600 text-sm mb-6 bg-slate-50 rounded-xl p-4">{{ $activeTask->description }}</p>
            @endif

            <h4 class="font-bold text-slate-800 mb-4">Comments ({{ $activeTask->comments->count() }})</h4>
            <div class="space-y-4 mb-6">
                @forelse($activeTask->comments as $comment)
                <div class="flex gap-3">
                    <div class="w-8 h-8 rounded-full bg-primary/10 flex items-center justify-center text-primary font-bold text-xs flex-shrink-0">
                        {{ strtoupper(substr($comment->user->name, 0, 2)) }}
                    </div>
                    <div class="flex-1 bg-slate-50 rounded-xl p-3">
                        <div class="flex items-center justify-between mb-1">
                            <p class="text-xs font-bold text-slate-700">{{ $comment->user->name }}</p>
                            <div class="flex items-center gap-2">
                                <p class="text-xs text-slate-400">{{ $comment->created_at->diffForHumans() }}</p>
                                @if($comment->user_id === auth()->id() || auth()->user()->isHRAdmin())
                                <button wire:click="deleteComment({{ $comment->id }})" class="text-slate-300 hover:text-error transition-colors">
                                    <span class="material-symbols-outlined text-xs">delete</span>
                                </button>
                                @endif
                            </div>
                        </div>
                        <p class="text-sm text-slate-600">{{ $comment->body }}</p>
                    </div>
                </div>
                @empty
                <p class="text-slate-400 text-sm text-center py-4">No comments yet.</p>
                @endforelse
            </div>

            {{-- Comments allowed for everyone who can see the task --}}
            <form wire:submit="addComment" class="flex gap-3">
                <input wire:model="commentBody" type="text" placeholder="Add a comment..."
                    class="flex-1 px-4 py-2 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-primary/20 focus:outline-none"/>
                <button type="submit" class="bg-primary text-on-primary px-4 py-2 rounded-xl font-bold text-sm hover:opacity-90 transition-opacity">Post</button>
            </form>
            @error('commentBody') <p class="text-error text-xs mt-1">{{ $message }}</p> @enderror
        </div>
    </div>
    @endif
</div>

<div>
    @if (session()->has('message'))
        <div class="mb-6 px-4 py-3 rounded-xl bg-green-50 border border-green-200 text-green-700 text-sm flex items-center gap-2">
            <span class="material-symbols-outlined text-sm">check_circle</span>
            {{ session('message') }}
        </div>
    @endif
    @if (session()->has('error'))
        <div class="mb-6 px-4 py-3 rounded-xl bg-red-50 border border-red-200 text-red-700 text-sm flex items-center gap-2">
            <span class="material-symbols-outlined text-sm">error</span>
            {{ session('error') }}
        </div>
    @endif

    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
        <div>
            <h2 class="text-2xl md:text-3xl font-extrabold text-slate-900 font-headline">Tasks</h2>
            <p class="text-slate-500 text-sm mt-1">
                @if($isEmployee) Your personal tasks and assignments @else Track and manage work assignments @endif
            </p>
        </div>
        <button wire:click="openCreate"
            class="bg-gradient-to-br from-primary to-primary-container text-on-primary px-5 py-2.5 rounded-xl font-bold font-headline shadow-lg shadow-primary/20 hover:opacity-90 transition-opacity flex items-center justify-center gap-2 w-full sm:w-auto">
            <span class="material-symbols-outlined text-sm">add</span> New Task
        </button>
    </div>

    {{-- Filters --}}
    <div class="glass-card rounded-[1.5rem] p-4 mb-6">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3 items-center">
            <input wire:model.live.debounce.300ms="search" type="text" placeholder="Search tasks..."
                class="px-4 py-2 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-primary/20 focus:outline-none"/>
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
            <select wire:model.live="labelFilter" class="px-4 py-2 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-primary/20 focus:outline-none">
                <option value="">All Labels</option>
                @foreach($availableLabels as $label)
                <option value="{{ $label }}">{{ $label }}</option>
                @endforeach
            </select>
            @if(!$isEmployee)
            <label class="flex items-center gap-2 text-sm font-semibold text-slate-600 cursor-pointer">
                <input wire:model.live="myTasksOnly" type="checkbox" class="rounded"/> My Tasks
            </label>
            @endif
        </div>
    </div>

    {{-- Task List --}}
    <div class="space-y-3">
        @forelse($tasks as $task)
        @php
            $priorityColors = ['urgent'=>'bg-red-100 text-red-700','high'=>'bg-orange-100 text-orange-700','medium'=>'bg-amber-100 text-amber-700','low'=>'bg-slate-100 text-slate-600'];
            $statusColors = ['pending'=>'bg-amber-100 text-amber-700','in_progress'=>'bg-blue-100 text-blue-700','completed'=>'bg-green-100 text-green-700','cancelled'=>'bg-slate-100 text-slate-500'];
            $isOwner = $task->created_by === auth()->id();
            $isPrivate = $task->visibility === 'private';
        @endphp
        <div class="glass-card rounded-[1.5rem] p-4 md:p-5 {{ $isPrivate && !$isOwner ? 'border-l-4 border-slate-300' : '' }}">
            <div class="flex items-start gap-3 md:gap-4">
                <div class="flex-1 min-w-0 cursor-pointer" wire:click="viewTask({{ $task->id }})">
                    <div class="flex items-center gap-1.5 md:gap-2 mb-1 flex-wrap">
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
                    </div>
                    <p class="font-bold text-slate-800 truncate">{{ $task->title }}</p>
                    <p class="text-xs text-slate-400 mt-0.5">
                        By {{ $task->creator->name }}
                        @if($task->assignee && $task->assignee->id !== $task->creator->id) · Assigned to {{ $task->assignee->name }} @endif
                        @if($task->due_date) · Due {{ $task->due_date->format('M d, Y') }} @endif
                    </p>

                    @if(!empty($task->labels))
                    <div class="flex items-center gap-1.5 mt-2 flex-wrap">
                        @foreach($task->labels as $label)
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold bg-indigo-50 text-indigo-600 border border-indigo-100">{{ $label }}</span>
                        @endforeach
                    </div>
                    @endif

                    @if($task->comments_count || $task->attachments_count || $task->watchers_count || $task->checklist_items_count)
                    <div class="flex items-center gap-3 mt-2 text-[11px] text-slate-400">
                        @if($task->checklist_items_count)
                        <span class="flex items-center gap-1">
                            <span class="material-symbols-outlined text-[13px]">checklist</span>
                            {{ $task->completed_checklist_items_count }}/{{ $task->checklist_items_count }}
                        </span>
                        @endif
                        @if($task->comments_count)
                        <span class="flex items-center gap-1"><span class="material-symbols-outlined text-[13px]">chat_bubble</span>{{ $task->comments_count }}</span>
                        @endif
                        @if($task->attachments_count)
                        <span class="flex items-center gap-1"><span class="material-symbols-outlined text-[13px]">attach_file</span>{{ $task->attachments_count }}</span>
                        @endif
                        @if($task->watchers_count)
                        <span class="flex items-center gap-1"><span class="material-symbols-outlined text-[13px]">visibility</span>{{ $task->watchers_count }}</span>
                        @endif
                    </div>
                    @endif
                </div>
                <div class="flex items-center gap-2 flex-shrink-0">
                    @if($task->status !== 'completed' && $task->status !== 'cancelled' && ($isOwner || $task->assigned_to === auth()->id()))
                    <select wire:change="updateStatus({{ $task->id }}, $event.target.value)"
                        class="text-xs px-2 md:px-3 py-1.5 rounded-xl border border-slate-200 focus:ring-2 focus:ring-primary/20 focus:outline-none">
                        <option value="pending" {{ $task->status==='pending'?'selected':'' }}>Pending</option>
                        <option value="in_progress" {{ $task->status==='in_progress'?'selected':'' }}>In Progress</option>
                        <option value="completed" {{ $task->status==='completed'?'selected':'' }}>Completed</option>
                        <option value="cancelled" {{ $task->status==='cancelled'?'selected':'' }}>Cancelled</option>
                    </select>
                    @endif
                    @if($isOwner || auth()->user()->isHRAdmin())
                    <button wire:click="openEdit({{ $task->id }})" class="p-2 rounded-xl text-slate-400 hover:text-primary hover:bg-slate-50 transition-colors" title="Edit">
                        <span class="material-symbols-outlined text-sm">edit</span>
                    </button>
                    @endif
                    @if(auth()->user()->isHRAdmin())
                    <button wire:click="delete({{ $task->id }})" wire:confirm="Delete this task?" class="p-2 rounded-xl text-slate-400 hover:text-error hover:bg-slate-50 transition-colors">
                        <span class="material-symbols-outlined text-sm">delete</span>
                    </button>
                    @endif
                </div>
            </div>
        </div>
        @empty
        <div class="glass-card rounded-[2rem] py-16 md:py-20 text-center text-slate-400">
            <span class="material-symbols-outlined text-5xl block mb-3">task_alt</span>
            <p>No tasks found.</p>
        </div>
        @endforelse
    </div>

    <div class="mt-4">{{ $tasks->links() }}</div>

    {{-- Create/Edit Modal --}}
    @if($showModal)
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm p-4">
        <div class="bg-white rounded-[2rem] shadow-2xl w-full max-w-lg p-6 md:p-8 max-h-[90vh] overflow-y-auto">
            <h3 class="text-xl md:text-2xl font-bold font-headline text-slate-900 mb-6">{{ $editing ? 'Edit' : 'New' }} Task</h3>
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
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
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

                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-2">Visibility</label>
                    <div class="grid grid-cols-2 gap-3">
                        <label class="flex items-center gap-3 p-3 rounded-xl border-2 cursor-pointer transition-colors {{ $visibility === 'team' ? 'border-primary bg-primary/5' : 'border-slate-200' }}">
                            <input wire:model.live="visibility" type="radio" value="team" class="sr-only"/>
                            <span class="material-symbols-outlined text-sm {{ $visibility === 'team' ? 'text-primary' : 'text-slate-400' }}">groups</span>
                            <div>
                                <p class="text-sm font-bold {{ $visibility === 'team' ? 'text-primary' : 'text-slate-700' }}">Team</p>
                                <p class="text-xs text-slate-400">Visible to managers</p>
                            </div>
                        </label>
                        <label class="flex items-center gap-3 p-3 rounded-xl border-2 cursor-pointer transition-colors {{ $visibility === 'private' ? 'border-primary bg-primary/5' : 'border-slate-200' }}">
                            <input wire:model.live="visibility" type="radio" value="private" class="sr-only"/>
                            <span class="material-symbols-outlined text-sm {{ $visibility === 'private' ? 'text-primary' : 'text-slate-400' }}">lock</span>
                            <div>
                                <p class="text-sm font-bold {{ $visibility === 'private' ? 'text-primary' : 'text-slate-700' }}">Private</p>
                                <p class="text-xs text-slate-400">You + your manager</p>
                            </div>
                        </label>
                    </div>
                </div>

                @if(!$isEmployee)
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
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

    {{-- Task Detail Drawer --}}
    @if($showTask && $activeTask)
        @include('livewire.tasks.partials.task-detail')
    @endif
</div>

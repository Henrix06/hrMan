@php
    $canEdit = $activeTask->created_by === auth()->id() || auth()->user()->isHRAdmin();
    $isWatching = $activeTask->watchers->contains(fn ($watcher) => $watcher->user_id === auth()->id());
    $progress = $activeTask->checklistProgress();
    $watcherIds = $activeTask->watchers->pluck('user_id');
    $watcherCandidates = collect($employees)->reject(fn ($employee) => $watcherIds->contains($employee->id));
    $priorityColors = ['urgent'=>'bg-red-100 text-red-700','high'=>'bg-orange-100 text-orange-700','medium'=>'bg-amber-100 text-amber-700','low'=>'bg-slate-100 text-slate-600'];
    $statusColors = ['pending'=>'bg-amber-100 text-amber-700','in_progress'=>'bg-blue-100 text-blue-700','completed'=>'bg-green-100 text-green-700','cancelled'=>'bg-slate-100 text-slate-500'];
@endphp

<div class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm p-4">
    <div class="bg-white rounded-[2rem] shadow-2xl w-full max-w-3xl p-6 md:p-8 max-h-[90vh] overflow-y-auto">

        {{-- Header --}}
        <div class="flex items-start justify-between gap-4 mb-5">
            <div class="min-w-0 flex-1">
                <div class="flex items-center gap-2 mb-1 flex-wrap">
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-widest {{ $priorityColors[$activeTask->priority] }}">{{ $activeTask->priority }}</span>
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-widest {{ $statusColors[$activeTask->status] }}">{{ str_replace('_',' ',$activeTask->status) }}</span>
                    @if($activeTask->visibility === 'private')
                    <span class="flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-500">
                        <span class="material-symbols-outlined text-[11px]">lock</span> Private
                    </span>
                    @endif
                    @if($activeTask->isOverdue())
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-widest bg-red-100 text-red-700">Overdue</span>
                    @endif
                </div>
                <h3 class="text-xl md:text-2xl font-bold font-headline text-slate-900">{{ $activeTask->title }}</h3>
                <p class="text-sm text-slate-500 mt-1">
                    Created by {{ $activeTask->creator->name }}
                    @if($activeTask->assignee) · Assigned to {{ $activeTask->assignee->name }} @endif
                    @if($activeTask->due_date) · Due {{ $activeTask->due_date->format('M d, Y') }} @endif
                </p>
            </div>
            <div class="flex items-center gap-2 flex-shrink-0">
                <button wire:click="toggleWatch" title="{{ $isWatching ? 'Stop watching' : 'Watch this task' }}"
                    class="flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold transition-colors {{ $isWatching ? 'bg-primary/10 text-primary' : 'bg-slate-100 text-slate-500 hover:bg-slate-200' }}">
                    <span class="material-symbols-outlined text-sm">{{ $isWatching ? 'visibility' : 'visibility_off' }}</span>
                    {{ $isWatching ? 'Watching' : 'Watch' }}
                </button>
                <button wire:click="$set('showTask', false)" class="p-1 rounded-lg text-slate-400 hover:text-slate-700 hover:bg-slate-100 transition-colors">
                    <span class="material-symbols-outlined">close</span>
                </button>
            </div>
        </div>

        @if($activeTask->description)
        <p class="text-slate-600 text-sm mb-6 bg-slate-50 rounded-xl p-4">{{ $activeTask->description }}</p>
        @endif

        {{-- Labels --}}
        <div class="mb-6">
            <h4 class="text-xs font-bold uppercase tracking-widest text-slate-400 mb-2">Labels</h4>
            <div class="flex items-center gap-1.5 flex-wrap">
                @forelse($activeTask->labels ?? [] as $label)
                <span class="flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-semibold bg-indigo-50 text-indigo-600 border border-indigo-100">
                    {{ $label }}
                    @if($canEdit)
                    <button wire:click="removeLabel(@js($label))" class="text-indigo-300 hover:text-indigo-700 transition-colors">
                        <span class="material-symbols-outlined text-[13px]">close</span>
                    </button>
                    @endif
                </span>
                @empty
                <span class="text-xs text-slate-400">No labels yet.</span>
                @endforelse
            </div>
            @if($canEdit)
            <form wire:submit="addLabel" class="flex items-center gap-2 mt-3">
                <input wire:model="labelInput" type="text" placeholder="Add labels, comma separated..."
                    class="flex-1 px-3 py-1.5 rounded-xl border border-slate-200 text-xs focus:ring-2 focus:ring-primary/20 focus:outline-none"/>
                <button type="submit" class="px-3 py-1.5 rounded-xl bg-slate-100 text-slate-700 text-xs font-bold hover:bg-slate-200 transition-colors">Add</button>
            </form>
            @error('labelInput') <p class="text-error text-xs mt-1">{{ $message }}</p> @enderror
            @endif
        </div>

        {{-- Checklist --}}
        <div class="mb-6">
            <div class="flex items-center justify-between mb-2">
                <h4 class="text-xs font-bold uppercase tracking-widest text-slate-400">Checklist</h4>
                @if($progress['total'])
                <span class="text-xs font-semibold text-slate-500">{{ $progress['done'] }}/{{ $progress['total'] }}</span>
                @endif
            </div>
            @if($progress['total'])
            <div class="w-full h-1.5 rounded-full bg-slate-100 mb-3 overflow-hidden">
                <div class="h-full bg-primary rounded-full transition-all duration-300" style="width: {{ $progress['percent'] }}%"></div>
            </div>
            @endif
            <div class="space-y-1.5">
                @forelse($activeTask->checklistItems as $item)
                <div class="group flex items-center gap-2.5 px-3 py-2 rounded-xl hover:bg-slate-50 transition-colors">
                    <button wire:click="toggleChecklistItem({{ $item->id }})" class="flex-shrink-0">
                        <span class="material-symbols-outlined {{ $item->is_completed ? 'text-primary' : 'text-slate-300' }}">
                            {{ $item->is_completed ? 'check_box' : 'check_box_outline_blank' }}
                        </span>
                    </button>
                    <span class="flex-1 text-sm {{ $item->is_completed ? 'line-through text-slate-400' : 'text-slate-700' }}">{{ $item->label }}</span>
                    <button wire:click="removeChecklistItem({{ $item->id }})" class="opacity-0 group-hover:opacity-100 text-slate-300 hover:text-error transition-all">
                        <span class="material-symbols-outlined text-sm">delete</span>
                    </button>
                </div>
                @empty
                <p class="text-xs text-slate-400">No checklist items yet.</p>
                @endforelse
            </div>
            <form wire:submit="addChecklistItem" class="flex items-center gap-2 mt-3">
                <input wire:model="checklistInput" type="text" placeholder="Add a checklist item..."
                    class="flex-1 px-3 py-1.5 rounded-xl border border-slate-200 text-xs focus:ring-2 focus:ring-primary/20 focus:outline-none"/>
                <button type="submit" class="px-3 py-1.5 rounded-xl bg-slate-100 text-slate-700 text-xs font-bold hover:bg-slate-200 transition-colors">Add</button>
            </form>
            @error('checklistInput') <p class="text-error text-xs mt-1">{{ $message }}</p> @enderror
        </div>

        {{-- Attachments --}}
        <div class="mb-6">
            <h4 class="text-xs font-bold uppercase tracking-widest text-slate-400 mb-2">Attachments</h4>
            <div class="space-y-2">
                @forelse($activeTask->attachments as $attachment)
                @php $canRemove = $attachment->user_id === auth()->id() || $canEdit; @endphp
                <div class="flex items-center gap-3 px-3 py-2 rounded-xl bg-slate-50">
                    <span class="material-symbols-outlined text-slate-400">
                        {{ $attachment->isImage() ? 'image' : 'description' }}
                    </span>
                    <div class="min-w-0 flex-1">
                        <a href="{{ route('tasks.attachments.download', $attachment) }}"
                            class="text-sm font-semibold text-slate-700 hover:text-primary truncate block">{{ $attachment->original_name }}</a>
                        <p class="text-[11px] text-slate-400">{{ $attachment->humanSize() }} · {{ $attachment->uploader?->name }} · {{ $attachment->created_at->diffForHumans() }}</p>
                    </div>
                    @if($canRemove)
                    <button wire:click="removeAttachment({{ $attachment->id }})" wire:confirm="Remove this attachment?"
                        class="text-slate-300 hover:text-error transition-colors">
                        <span class="material-symbols-outlined text-sm">delete</span>
                    </button>
                    @endif
                </div>
                @empty
                <p class="text-xs text-slate-400">No files attached.</p>
                @endforelse
            </div>
            <form wire:submit="uploadAttachment" class="flex items-center gap-2 mt-3">
                <input wire:model="attachmentUpload" type="file"
                    accept="{{ collect(config('tasks.allowed_attachment_extensions', []))->map(fn ($ext) => '.'.$ext)->implode(',') }}"
                    class="flex-1 text-xs text-slate-500 file:mr-3 file:px-3 file:py-1.5 file:rounded-xl file:border-0 file:bg-slate-100 file:text-slate-700 file:text-xs file:font-bold hover:file:bg-slate-200"/>
                <button type="submit" class="px-3 py-1.5 rounded-xl bg-primary text-on-primary text-xs font-bold hover:opacity-90 transition-opacity">
                    <span wire:loading.remove wire:target="attachmentUpload">Attach</span>
                    <span wire:loading wire:target="attachmentUpload">Uploading…</span>
                </button>
            </form>
            @error('attachmentUpload') <p class="text-error text-xs mt-1">{{ $message }}</p> @enderror
        </div>

        {{-- Watchers --}}
        <div class="mb-6">
            <h4 class="text-xs font-bold uppercase tracking-widest text-slate-400 mb-2">Watchers ({{ $activeTask->watchers->count() }})</h4>
            <div class="flex items-center gap-2 flex-wrap">
                @forelse($activeTask->watchers as $watcher)
                <span class="group flex items-center gap-1.5 pl-1 pr-2 py-1 rounded-full bg-slate-50 border border-slate-100">
                    <span class="w-6 h-6 rounded-full bg-primary/10 flex items-center justify-center text-primary font-bold text-[10px]">
                        {{ strtoupper(substr($watcher->user?->name ?? '?', 0, 2)) }}
                    </span>
                    <span class="text-[11px] font-semibold text-slate-600">{{ $watcher->user?->name ?? 'Former teammate' }}</span>
                    @if($watcher->user_id === auth()->id() || $canEdit)
                    <button wire:click="removeWatcher({{ $watcher->user_id }})" class="text-slate-300 hover:text-error transition-colors">
                        <span class="material-symbols-outlined text-[13px]">close</span>
                    </button>
                    @endif
                </span>
                @empty
                <span class="text-xs text-slate-400">Nobody is following this task yet.</span>
                @endforelse
            </div>
            @if($canEdit && $watcherCandidates->isNotEmpty())
            <form wire:submit="addWatcher" class="flex items-center gap-2 mt-3">
                <select wire:model="watcherToAdd" class="flex-1 px-3 py-1.5 rounded-xl border border-slate-200 text-xs focus:ring-2 focus:ring-primary/20 focus:outline-none">
                    <option value="">Add a watcher…</option>
                    @foreach($watcherCandidates as $candidate)
                    <option value="{{ $candidate->id }}">{{ $candidate->name }}</option>
                    @endforeach
                </select>
                <button type="submit" class="px-3 py-1.5 rounded-xl bg-slate-100 text-slate-700 text-xs font-bold hover:bg-slate-200 transition-colors">Add</button>
            </form>
            @error('watcherToAdd') <p class="text-error text-xs mt-1">{{ $message }}</p> @enderror
            @endif
        </div>

        {{-- Activity & comments --}}
        <div class="border-t border-slate-100 pt-5">
            <h4 class="text-xs font-bold uppercase tracking-widest text-slate-400 mb-4">Activity</h4>
            <div class="space-y-4 mb-6">
                @forelse($this->timeline() as $entry)
                @if($entry['kind'] === 'comment')
                <div class="flex gap-3">
                    <div class="w-8 h-8 rounded-full bg-primary/10 flex items-center justify-center text-primary font-bold text-xs flex-shrink-0">
                        {{ strtoupper(substr($entry['user']?->name ?? '?', 0, 2)) }}
                    </div>
                    <div class="flex-1 bg-slate-50 rounded-xl p-3">
                        <div class="flex items-center justify-between mb-1">
                            <p class="text-xs font-bold text-slate-700">{{ $entry['user']?->name ?? 'Former teammate' }}</p>
                            <div class="flex items-center gap-2">
                                <p class="text-xs text-slate-400">{{ $entry['created_at']->diffForHumans() }}</p>
                                @if($entry['user']?->id === auth()->id() || auth()->user()->isHRAdmin())
                                <button wire:click="deleteComment({{ $entry['id'] }})" class="text-slate-300 hover:text-error transition-colors">
                                    <span class="material-symbols-outlined text-xs">delete</span>
                                </button>
                                @endif
                            </div>
                        </div>
                        <p class="text-sm text-slate-600">{{ $entry['body'] }}</p>
                    </div>
                </div>
                @else
                <div class="flex gap-3">
                    <div class="w-8 h-8 rounded-full bg-slate-100 flex items-center justify-center text-slate-400 flex-shrink-0">
                        <span class="material-symbols-outlined text-[15px]">{{ $entry['icon'] }}</span>
                    </div>
                    <div class="flex-1 pt-0.5">
                        <p class="text-sm text-slate-600">{{ $entry['description'] }}</p>
                        <p class="text-[11px] text-slate-400 mt-0.5">{{ $entry['created_at']->diffForHumans() }}</p>
                    </div>
                </div>
                @endif
                @empty
                <p class="text-slate-400 text-sm text-center py-4">Nothing has happened yet.</p>
                @endforelse
            </div>

            <form wire:submit="addComment" class="flex flex-col gap-2">
                <div class="flex gap-3">
                    <input wire:model="commentBody" type="text" placeholder="Add a comment... use @name to mention a teammate"
                        class="flex-1 px-4 py-2 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-primary/20 focus:outline-none"/>
                    <button type="submit" class="bg-primary text-on-primary px-4 py-2 rounded-xl font-bold text-sm hover:opacity-90 transition-opacity">Post</button>
                </div>
                @error('commentBody') <p class="text-error text-xs">{{ $message }}</p> @enderror
            </form>
        </div>
    </div>
</div>

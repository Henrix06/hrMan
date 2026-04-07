<div>
    @if (session()->has('message'))
        <div class="mb-6 px-4 py-3 rounded-xl bg-green-50 border border-green-200 text-green-700 text-sm">{{ session('message') }}</div>
    @endif

    <div class="flex items-center justify-between mb-8">
        <div>
            <h2 class="text-3xl font-extrabold text-slate-900 font-headline">Notifications</h2>
            <p class="text-slate-500 text-sm mt-1">{{ $unreadCount }} unread</p>
        </div>
        @if($unreadCount > 0)
        <button wire:click="markAllRead" class="text-sm font-bold text-primary hover:bg-primary/5 px-4 py-2 rounded-xl transition-colors">
            Mark all as read
        </button>
        @endif
    </div>

    <div class="glass-card rounded-[2rem] overflow-hidden">
        @forelse($notifications as $notif)
        @php
            $icons = ['task_assigned'=>'task_alt','task_comment'=>'comment','announcement'=>'campaign','leave_approved'=>'check_circle','leave_rejected'=>'cancel','default'=>'notifications'];
            $icon = $icons[$notif->type] ?? $icons['default'];
        @endphp
        <div class="flex items-start gap-4 p-5 border-b border-slate-100 last:border-0 {{ !$notif->isRead() ? 'bg-primary/5' : '' }} hover:bg-slate-50/50 transition-colors">
            <div class="w-10 h-10 rounded-full bg-primary/10 flex items-center justify-center text-primary flex-shrink-0">
                <span class="material-symbols-outlined text-sm">{{ $icon }}</span>
            </div>
            <div class="flex-1 min-w-0">
                <p class="text-sm font-bold text-slate-800">{{ $notif->title }}</p>
                <p class="text-xs text-slate-500 mt-0.5">{{ $notif->message }}</p>
                <p class="text-xs text-slate-400 mt-1">{{ $notif->created_at->diffForHumans() }}</p>
            </div>
            <div class="flex items-center gap-2 flex-shrink-0">
                @if(!$notif->isRead())
                <button wire:click="markRead({{ $notif->id }})" class="text-xs text-primary font-bold hover:underline">Mark read</button>
                @endif
                <button wire:click="delete({{ $notif->id }})" class="text-slate-300 hover:text-error transition-colors">
                    <span class="material-symbols-outlined text-sm">close</span>
                </button>
            </div>
        </div>
        @empty
        <div class="py-20 text-center text-slate-400">
            <span class="material-symbols-outlined text-5xl block mb-3">notifications_none</span>
            <p>You're all caught up.</p>
        </div>
        @endforelse
    </div>

    <div class="mt-4">{{ $notifications->links() }}</div>
</div>

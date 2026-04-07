<?php

namespace App\Livewire\Notifications;

use App\Models\HrNotification;
use Illuminate\Support\Facades\Cache;
use Livewire\Component;
use Livewire\WithPagination;

class NotificationCenter extends Component
{
    use WithPagination;

    private function bustCache(): void
    {
        Cache::forget('unread_notif_' . auth()->id());
    }

    public function markRead(int $id): void
    {
        HrNotification::where('id', $id)->where('user_id', auth()->id())
            ->update(['read_at' => now()]);
        $this->bustCache();
    }

    public function markAllRead(): void
    {
        HrNotification::where('user_id', auth()->id())
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
        $this->bustCache();
        session()->flash('message', 'All notifications marked as read.');
    }

    public function delete(int $id): void
    {
        HrNotification::where('id', $id)->where('user_id', auth()->id())->delete();
        $this->bustCache();
    }

    public function render()
    {
        $userId = auth()->id();

        $notifications = HrNotification::where('user_id', $userId)
            ->orderByDesc('created_at')
            ->paginate(20);

        $unreadCount = Cache::remember("unread_notif_{$userId}", 30,
            fn() => HrNotification::where('user_id', $userId)->whereNull('read_at')->count()
        );

        return view('livewire.notifications.index', compact('notifications', 'unreadCount'));
    }
}

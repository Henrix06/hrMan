<?php

namespace App\Livewire\Chat;

use App\Models\ChatConversation;
use App\Models\ChatMessage;
use App\Models\User;
use Livewire\Component;

class ChatBox extends Component
{
    // 'list' | 'conversation'
    public string $view = 'list';

    public ?int $activeConversationId = null;
    public string $messageInput = '';
    public string $searchPeople = '';

    // Track last seen message id to detect new messages for polling
    public int $lastMessageId = 0;

    public function openConversation(int $userId): void
    {
        $conv = ChatConversation::between(auth()->id(), $userId);
        $this->activeConversationId = $conv->id;
        $this->view = 'conversation';
        $this->messageInput = '';
        $this->markRead();
        $this->lastMessageId = $conv->messages()->max('id') ?? 0;
    }

    public function backToList(): void
    {
        $this->view = 'list';
        $this->activeConversationId = null;
    }

    public function sendMessage(): void
    {
        // messageInput is set by Alpine via $wire.set() before calling this
        $body = trim($this->messageInput);
        if (!$body || !$this->activeConversationId) return;

        $msg = ChatMessage::create([
            'conversation_id' => $this->activeConversationId,
            'sender_id' => auth()->id(),
            'body' => $body,
        ]);

        ChatConversation::where('id', $this->activeConversationId)
            ->update(['last_message_at' => now()]);

        $this->messageInput = '';
        $this->lastMessageId = $msg->id;
    }

    // Called by Livewire polling — only writes if there are actually unread messages
    public function pollMessages(): void
    {
        if ($this->view === 'conversation' && $this->activeConversationId) {
            $hasUnread = ChatMessage::where('conversation_id', $this->activeConversationId)
                ->where('sender_id', '!=', auth()->id())
                ->whereNull('read_at')
                ->exists(); // exists() is cheaper than count()

            if ($hasUnread) {
                $this->markRead();
            }
        }
    }

    private function markRead(): void
    {
        if (!$this->activeConversationId) return;

        ChatMessage::where('conversation_id', $this->activeConversationId)
            ->where('sender_id', '!=', auth()->id())
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    }

    public function render()
    {
        $me = auth()->id();

        // Single query: get unread counts per conversation using a subquery
        // instead of calling unreadCountFor() in a loop (N+1)
        $unreadCounts = ChatMessage::selectRaw('conversation_id, COUNT(*) as cnt')
            ->where('sender_id', '!=', $me)
            ->whereNull('read_at')
            ->whereIn('conversation_id', function ($sub) use ($me) {
                $sub->select('id')->from('chat_conversations')
                    ->where('user_one_id', $me)->orWhere('user_two_id', $me);
            })
            ->groupBy('conversation_id')
            ->pluck('cnt', 'conversation_id');

        $totalUnread = $unreadCounts->sum();

        $conversations = ChatConversation::with(['userOne', 'userTwo', 'latestMessage'])
            ->where(fn($q) => $q->where('user_one_id', $me)->orWhere('user_two_id', $me))
            ->orderByDesc('last_message_at')
            ->get()
            ->map(fn(ChatConversation $conv) => [
                'id'     => $conv->id,
                'other'  => $conv->otherUser($me),
                'latest' => $conv->latestMessage,
                'unread' => $unreadCounts->get($conv->id, 0),
            ]);

        // People search
        $people = collect();
        if (strlen($this->searchPeople) >= 1) {
            $people = User::where('id', '!=', $me)
                ->where('name', 'like', "%{$this->searchPeople}%")
                ->orderBy('name')
                ->limit(8)
                ->get(['id', 'name', 'position']); // select only needed columns
        }

        // Active conversation — cap messages at 100 most recent to prevent memory bloat
        $messages = collect();
        $otherUser = null;
        if ($this->activeConversationId) {
            $conv = ChatConversation::with(['userOne', 'userTwo'])->find($this->activeConversationId);
            if ($conv) {
                $otherUser = $conv->otherUser($me);
                $messages = ChatMessage::with('sender:id,name')  // select only needed sender columns
                    ->where('conversation_id', $this->activeConversationId)
                    ->orderBy('created_at')
                    ->limit(100)
                    ->get();
            }
        }

        return view('livewire.chat.chat-box', compact(
            'conversations', 'totalUnread', 'people', 'messages', 'otherUser'
        ));
    }
}

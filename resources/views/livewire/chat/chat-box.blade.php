<div
    x-data="{
        open: false,
        optimistic: [],
        input: '',
        toggle() { this.open = !this.open; },
        send() {
            const body = this.input.trim();
            if (!body) return;
            this.optimistic.push({ id: 'opt-' + Date.now(), body: body, time: new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }) });
            this.input = '';
            this.$nextTick(() => this.scrollBottom());
            $wire.set('messageInput', body);
            $wire.sendMessage().then(() => { this.optimistic = []; });
        },
        scrollBottom() {
            const el = document.getElementById('chat-messages');
            if (el) el.scrollTop = el.scrollHeight;
        }
    }"
    wire:poll.3s="pollMessages"
>
    {{-- Toggle Button --}}
    <button @click="toggle()"
        class="fixed bottom-6 right-6 z-50 w-14 h-14 rounded-full bg-gradient-to-br from-primary to-primary-container text-on-primary shadow-2xl shadow-primary/30 flex items-center justify-center hover:scale-105 active:scale-95 transition-transform"
        aria-label="Toggle chat">
        <span class="material-symbols-outlined" x-text="open ? 'close' : 'chat'"></span>
        @if($totalUnread > 0)
        <span x-show="!open"
            class="absolute -top-1 -right-1 w-5 h-5 bg-error rounded-full border-2 border-white text-white text-[9px] font-bold flex items-center justify-center">
            {{ $totalUnread > 9 ? '9+' : $totalUnread }}
        </span>
        @endif
    </button>

    {{-- Chat Window --}}
    <div x-show="open" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-2" x-transition:enter-end="opacity-100 translate-y-0" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 translate-y-0" x-transition:leave-end="opacity-0 translate-y-2"
        class="fixed bottom-24 right-6 z-50 w-80 bg-white rounded-[1.75rem] shadow-2xl shadow-slate-900/20 flex flex-col overflow-hidden border border-slate-100"
        style="height:480px;" x-cloak>

        @if($view === 'list')
        <div class="flex flex-col h-full">
            <div class="px-5 pt-5 pb-3 border-b border-slate-100">
                <h3 class="text-lg font-bold font-headline text-slate-900">Messages</h3>
                <div class="relative mt-3">
                    <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-sm">search</span>
                    <input wire:model.live.debounce.250ms="searchPeople" type="text" placeholder="Find someone..."
                        class="w-full pl-9 pr-4 py-2 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-primary/20 focus:outline-none"/>
                </div>
            </div>
            <div class="flex-1 overflow-y-auto">
                @if(strlen($searchPeople) >= 1)
                <div class="px-3 pt-2 pb-1">
                    <p class="text-[10px] font-bold uppercase tracking-widest text-slate-400 px-2 mb-1">People</p>
                    @forelse($people as $person)
                    <button wire:click="openConversation({{ $person->id }})" class="w-full flex items-center gap-3 px-3 py-2.5 rounded-xl hover:bg-slate-50 transition-colors text-left">
                        <div class="w-9 h-9 rounded-full bg-primary/10 flex items-center justify-center text-primary font-bold text-sm flex-shrink-0">{{ strtoupper(substr($person->name, 0, 2)) }}</div>
                        <div class="min-w-0">
                            <p class="text-sm font-semibold text-slate-800 truncate">{{ $person->name }}</p>
                            <p class="text-xs text-slate-400 truncate">{{ $person->position ?? $person->roles->first()?->name }}</p>
                        </div>
                    </button>
                    @empty
                    <p class="text-xs text-slate-400 px-3 py-4 text-center">No results for "{{ $searchPeople }}"</p>
                    @endforelse
                </div>
                @else
                @forelse($conversations as $conv)
                <button wire:click="openConversation({{ $conv['other']->id }})" class="w-full flex items-center gap-3 px-4 py-3 hover:bg-slate-50 transition-colors text-left border-b border-slate-50 last:border-0">
                    <div class="relative flex-shrink-0">
                        <div class="w-10 h-10 rounded-full bg-primary/10 flex items-center justify-center text-primary font-bold text-sm">{{ strtoupper(substr($conv['other']->name, 0, 2)) }}</div>
                        @if($conv['unread'] > 0)
                        <span class="absolute -top-0.5 -right-0.5 w-4 h-4 bg-error rounded-full border-2 border-white text-white text-[8px] font-bold flex items-center justify-center">{{ $conv['unread'] }}</span>
                        @endif
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center justify-between">
                            <p class="text-sm font-{{ $conv['unread'] > 0 ? 'bold' : 'semibold' }} text-slate-800 truncate">{{ $conv['other']->name }}</p>
                            @if($conv['latest'])<p class="text-[10px] text-slate-400 flex-shrink-0 ml-1">{{ $conv['latest']->created_at->format('H:i') }}</p>@endif
                        </div>
                        @if($conv['latest'])
                        <p class="text-xs text-slate-400 truncate mt-0.5 {{ $conv['unread'] > 0 ? 'font-semibold text-slate-600' : '' }}">
                            {{ $conv['latest']->sender_id === auth()->id() ? 'You: ' : '' }}{{ $conv['latest']->body }}
                        </p>
                        @endif
                    </div>
                </button>
                @empty
                <div class="flex flex-col items-center justify-center h-48 text-slate-400">
                    <span class="material-symbols-outlined text-4xl mb-2">forum</span>
                    <p class="text-sm">No conversations yet.</p>
                    <p class="text-xs mt-1">Search for someone above.</p>
                </div>
                @endforelse
                @endif
            </div>
        </div>

        @elseif($view === 'conversation')
        <div class="flex flex-col h-full">
            <div class="flex items-center gap-3 px-4 py-3 border-b border-slate-100 flex-shrink-0">
                <button wire:click="backToList" class="text-slate-400 hover:text-primary transition-colors">
                    <span class="material-symbols-outlined text-sm">arrow_back</span>
                </button>
                @if($otherUser)
                <div class="w-8 h-8 rounded-full bg-primary/10 flex items-center justify-center text-primary font-bold text-xs flex-shrink-0">{{ strtoupper(substr($otherUser->name, 0, 2)) }}</div>
                <div class="min-w-0">
                    <p class="text-sm font-bold text-slate-900 truncate font-headline">{{ $otherUser->name }}</p>
                    <p class="text-[10px] text-slate-400 truncate">{{ $otherUser->position ?? $otherUser->roles->first()?->name }}</p>
                </div>
                @endif
            </div>
            <div class="flex-1 overflow-y-auto px-4 py-3 space-y-2" id="chat-messages"
                x-init="$nextTick(() => scrollBottom())"
                wire:updated="$nextTick(() => scrollBottom())">
                @forelse($messages as $msg)
                @php $isMine = $msg->sender_id === auth()->id(); @endphp
                <div class="flex {{ $isMine ? 'justify-end' : 'justify-start' }} gap-2">
                    @if(!$isMine)
                    <div class="w-6 h-6 rounded-full bg-primary/10 flex items-center justify-center text-primary font-bold text-[10px] flex-shrink-0 mt-auto">{{ strtoupper(substr($msg->sender->name, 0, 1)) }}</div>
                    @endif
                    <div class="max-w-[75%]">
                        <div class="px-3 py-2 rounded-2xl text-sm leading-relaxed {{ $isMine ? 'bg-primary text-on-primary rounded-br-sm' : 'bg-slate-100 text-slate-800 rounded-bl-sm' }}">{{ $msg->body }}</div>
                        <p class="text-[9px] text-slate-400 mt-0.5 {{ $isMine ? 'text-right' : 'text-left' }}">
                            {{ $msg->created_at->format('H:i') }}
                            @if($isMine && $msg->read_at)<span class="text-primary">&#10003;&#10003;</span>@endif
                        </p>
                    </div>
                </div>
                @empty
                <div class="flex flex-col items-center justify-center h-full text-slate-400 py-8">
                    <span class="material-symbols-outlined text-3xl mb-2">chat_bubble_outline</span>
                    <p class="text-xs">Say hello!</p>
                </div>
                @endforelse
                <template x-for="msg in optimistic" :key="msg.id">
                    <div class="flex justify-end gap-2">
                        <div class="max-w-[75%]">
                            <div class="px-3 py-2 rounded-2xl text-sm leading-relaxed bg-primary text-on-primary rounded-br-sm opacity-70" x-text="msg.body"></div>
                            <p class="text-[9px] text-slate-400 mt-0.5 text-right"><span x-text="msg.time"></span> <span class="text-slate-300">&#10003;</span></p>
                        </div>
                    </div>
                </template>
            </div>
            <div class="px-3 py-3 border-t border-slate-100 flex-shrink-0">
                <div class="flex items-center gap-2">
                    <input x-model="input" x-on:keydown.enter.prevent="send()" type="text"
                        placeholder="Type a message..." autocomplete="off"
                        class="flex-1 px-4 py-2 bg-slate-50 border border-slate-200 rounded-full text-sm focus:ring-2 focus:ring-primary/20 focus:outline-none"/>
                    <button @click="send()" type="button"
                        class="w-9 h-9 rounded-full bg-primary text-on-primary flex items-center justify-center hover:opacity-90 transition-opacity flex-shrink-0">
                        <span class="material-symbols-outlined text-sm">send</span>
                    </button>
                </div>
            </div>
        </div>
        @endif
    </div>
</div>

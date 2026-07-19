<div>
    @if (session()->has('message'))
        <div class="mb-6 px-4 py-3 rounded-xl bg-green-50 border border-green-200 text-green-700 text-sm">{{ session('message') }}</div>
    @endif

    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
        <div>
            <h2 class="text-2xl md:text-3xl font-extrabold text-slate-900 font-headline">Announcements</h2>
            <p class="text-slate-500 text-sm mt-1">Company-wide communications</p>
        </div>
        @if($canManage)
        <button wire:click="openCreate"
            class="bg-gradient-to-br from-primary to-primary-container text-on-primary px-5 py-2.5 rounded-xl font-bold font-headline shadow-lg shadow-primary/20 hover:opacity-90 transition-opacity flex items-center justify-center gap-2 w-full sm:w-auto">
            <span class="material-symbols-outlined text-sm">add</span> New Announcement
        </button>
        @endif
    </div>

    <div class="space-y-4">
        @forelse($announcements as $ann)
        <div class="glass-card rounded-[2rem] p-6 {{ $ann->pinned ? 'border-l-4 border-primary' : '' }}">
            <div class="flex items-start justify-between gap-4">
                <div class="flex-1">
                    <div class="flex items-center gap-2 mb-2">
                        @if($ann->pinned)
                        <span class="material-symbols-outlined text-primary text-sm">push_pin</span>
                        @endif
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-widest bg-primary/10 text-primary">{{ $ann->audience }}</span>
                        @if(!$ann->published_at)
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-widest bg-amber-100 text-amber-700">Draft</span>
                        @endif
                    </div>
                    <h3 class="text-xl font-bold font-headline text-slate-900 mb-2">{{ $ann->title }}</h3>
                    <p class="text-slate-600 text-sm leading-relaxed">{{ $ann->body }}</p>
                    <p class="text-xs text-slate-400 mt-3">
                        By {{ $ann->author->name }}
                        @if($ann->published_at) &middot; {{ $ann->published_at->format('M d, Y') }} @endif
                    </p>
                </div>
                @if($canManage)
                <div class="flex gap-2 flex-shrink-0">
                    <button wire:click="openEdit({{ $ann->id }})" class="text-slate-400 hover:text-primary transition-colors">
                        <span class="material-symbols-outlined text-sm">edit</span>
                    </button>
                    <button wire:click="delete({{ $ann->id }})" wire:confirm="Delete this announcement?" class="text-slate-400 hover:text-error transition-colors">
                        <span class="material-symbols-outlined text-sm">delete</span>
                    </button>
                </div>
                @endif
            </div>
        </div>
        @empty
        <div class="glass-card rounded-[2rem] py-20 text-center text-slate-400">
            <span class="material-symbols-outlined text-5xl block mb-3">campaign</span>
            <p>No announcements yet.</p>
        </div>
        @endforelse
    </div>

    <div class="mt-4">{{ $announcements->links() }}</div>

    @if($showModal)
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm p-4">
        <div class="bg-white rounded-[2rem] shadow-2xl w-full max-w-lg p-6 md:p-8 max-h-[90vh] overflow-y-auto">
            <h3 class="text-2xl font-bold font-headline text-slate-900 mb-6">{{ $editing ? 'Edit' : 'New' }} Announcement</h3>
            <form wire:submit="save" class="space-y-4">
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1">Title *</label>
                    <input wire:model="title" type="text" class="w-full px-4 py-2 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-primary/20 focus:outline-none"/>
                    @error('title') <p class="text-error text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1">Message *</label>
                    <textarea wire:model="body" rows="5" class="w-full px-4 py-2 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-primary/20 focus:outline-none"></textarea>
                    @error('body') <p class="text-error text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1">Audience</label>
                    <select wire:model="audience" class="w-full px-4 py-2 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-primary/20 focus:outline-none">
                        <option value="all">Everyone</option>
                        <option value="managers">Managers & Above</option>
                        <option value="employees">Employees Only</option>
                    </select>
                </div>
                <div class="flex items-center gap-4">
                    <label class="flex items-center gap-2 text-sm font-semibold text-slate-700 cursor-pointer">
                        <input wire:model="pinned" type="checkbox" class="rounded"/> Pin to top
                    </label>
                    <label class="flex items-center gap-2 text-sm font-semibold text-slate-700 cursor-pointer">
                        <input wire:model="publish_now" type="checkbox" class="rounded"/> Publish now
                    </label>
                </div>
                <div class="flex gap-3 pt-2">
                    <button type="submit" class="flex-1 bg-primary text-on-primary py-2.5 rounded-xl font-bold font-headline hover:opacity-90 transition-opacity">
                        {{ $editing ? 'Update' : 'Publish' }}
                    </button>
                    <button type="button" wire:click="$set('showModal', false)" class="flex-1 bg-slate-100 text-slate-700 py-2.5 rounded-xl font-bold font-headline hover:bg-slate-200 transition-colors">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>
    @endif
</div>

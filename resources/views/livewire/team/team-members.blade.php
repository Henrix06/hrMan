<div>
    <div class="mb-8">
        <h2 class="text-3xl font-extrabold text-slate-900 font-headline">Team</h2>
        <p class="text-slate-500 text-sm mt-1">Invite teammates by email — they choose their own password when they accept.</p>
    </div>

    @if($status_message)
    <div class="mb-6 px-4 py-3 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-700 text-sm flex items-start gap-2">
        <span class="material-symbols-outlined text-sm mt-0.5">check_circle</span>
        <span>{{ $status_message }}</span>
    </div>
    @endif

    @if($error_message)
    <div class="mb-6 px-4 py-3 rounded-xl bg-red-50 border border-red-200 text-red-700 text-sm flex items-start gap-2">
        <span class="material-symbols-outlined text-sm mt-0.5">error</span>
        <span>{{ $error_message }}</span>
    </div>
    @endif

    {{-- Invite form --}}
    <div class="glass-card p-8 rounded-[2rem] mb-8">
        <div class="flex items-start gap-4 mb-6">
            <div class="w-12 h-12 rounded-2xl bg-primary/10 flex items-center justify-center text-primary flex-shrink-0">
                <span class="material-symbols-outlined">person_add</span>
            </div>
            <div>
                <h3 class="text-lg font-bold font-headline text-slate-900">Invite a teammate</h3>
                <p class="text-sm text-slate-500">They'll receive an email with a secure link to set up their account.</p>
            </div>
        </div>

        <form wire:submit="invite" class="grid grid-cols-1 md:grid-cols-12 gap-4 items-end">
            <div class="md:col-span-5">
                <label for="invite-email" class="block text-xs font-semibold text-slate-500 uppercase tracking-widest mb-1.5">Work email</label>
                <input id="invite-email" wire:model="email" type="email" placeholder="teammate@company.com"
                    class="w-full px-4 py-3 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-primary/20 focus:outline-none"/>
                @error('email') <p class="mt-1.5 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <div class="md:col-span-3">
                <label for="invite-name" class="block text-xs font-semibold text-slate-500 uppercase tracking-widest mb-1.5">Name <span class="text-slate-300">(optional)</span></label>
                <input id="invite-name" wire:model="name" type="text" placeholder="Jane Doe"
                    class="w-full px-4 py-3 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-primary/20 focus:outline-none"/>
                @error('name') <p class="mt-1.5 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <div class="md:col-span-2">
                <label for="invite-role" class="block text-xs font-semibold text-slate-500 uppercase tracking-widest mb-1.5">Role</label>
                <select id="invite-role" wire:model="role"
                    class="w-full px-4 py-3 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-primary/20 focus:outline-none">
                    @foreach($this->roles as $roleOption)
                    <option value="{{ $roleOption }}">{{ $roleOption }}</option>
                    @endforeach
                </select>
                @error('role') <p class="mt-1.5 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <div class="md:col-span-2">
                <button type="submit" wire:loading.attr="disabled"
                    class="w-full px-5 py-3 rounded-xl bg-gradient-to-br from-primary to-primary-container text-white font-bold font-headline text-sm shadow-lg shadow-primary/20 hover:opacity-90 active:scale-[0.98] transition-all disabled:opacity-60 flex items-center justify-center gap-2">
                    <span wire:loading.remove class="flex items-center gap-2">
                        <span class="material-symbols-outlined text-sm">send</span>
                        Send invite
                    </span>
                    <span wire:loading>Sending…</span>
                </button>
            </div>
        </form>
    </div>

    {{-- Pending invitations --}}
    <div class="glass-card rounded-[2rem] overflow-hidden mb-8">
        <div class="p-6 border-b border-slate-100 flex items-center justify-between">
            <div>
                <h3 class="text-base font-bold font-headline text-slate-900">Pending invitations</h3>
                <p class="text-xs text-slate-400 mt-0.5">Waiting for these people to accept</p>
            </div>
            <span class="text-xs font-bold px-2.5 py-1 rounded-full bg-primary/10 text-primary">{{ $pending->count() }}</span>
        </div>

        <table class="w-full text-sm">
            <thead class="bg-slate-50/80">
                <tr>
                    <th class="text-left px-6 py-4 font-semibold text-slate-500 uppercase tracking-wider text-xs">Email</th>
                    <th class="text-left px-6 py-4 font-semibold text-slate-500 uppercase tracking-wider text-xs">Role</th>
                    <th class="text-left px-6 py-4 font-semibold text-slate-500 uppercase tracking-wider text-xs">Invited by</th>
                    <th class="text-left px-6 py-4 font-semibold text-slate-500 uppercase tracking-wider text-xs">Expires</th>
                    <th class="text-right px-6 py-4 font-semibold text-slate-500 uppercase tracking-wider text-xs">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($pending as $invitation)
                @php $expired = $invitation->isExpired(); @endphp
                <tr class="hover:bg-slate-50/50 transition-colors">
                    <td class="px-6 py-4">
                        <div class="font-semibold text-slate-700">{{ $invitation->email }}</div>
                        @if($invitation->name)
                        <div class="text-xs text-slate-400">{{ $invitation->name }}</div>
                        @endif
                    </td>
                    <td class="px-6 py-4 text-slate-600">{{ $invitation->role }}</td>
                    <td class="px-6 py-4 text-slate-500 text-xs">{{ $invitation->inviter?->name ?? '—' }}</td>
                    <td class="px-6 py-4">
                        @if($expired)
                            <span class="text-xs font-semibold px-2 py-0.5 rounded-full bg-red-50 text-red-600">Expired</span>
                        @else
                            <span class="text-slate-600 text-xs">{{ $invitation->expires_at?->diffForHumans() }}</span>
                        @endif
                    </td>
                    <td class="px-6 py-4">
                        <div class="flex items-center justify-end gap-2">
                            <button type="button" wire:click="resend('{{ $invitation->id }}')" wire:loading.attr="disabled"
                                class="px-3 py-1.5 rounded-lg border border-slate-200 text-xs font-semibold text-slate-600 hover:border-primary/40 hover:text-primary transition-colors disabled:opacity-50">
                                {{ $expired ? 'Resend' : 'Renew link' }}
                            </button>
                            <button type="button" wire:click="cancelInvitation('{{ $invitation->id }}')"
                                wire:confirm="Cancel the invitation for {{ $invitation->email }}?"
                                class="px-3 py-1.5 rounded-lg border border-slate-200 text-xs font-semibold text-slate-500 hover:border-red-300 hover:text-red-600 transition-colors">
                                Cancel
                            </button>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="px-6 py-14 text-center text-slate-400">
                        <span class="material-symbols-outlined text-4xl block mb-2">mark_email_read</span>
                        No pending invitations.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Members --}}
    <div class="glass-card rounded-[2rem] overflow-hidden">
        <div class="p-6 border-b border-slate-100 flex items-center justify-between">
            <div>
                <h3 class="text-base font-bold font-headline text-slate-900">Members</h3>
                <p class="text-xs text-slate-400 mt-0.5">Everyone with access to this workspace</p>
            </div>
            <span class="text-xs font-bold px-2.5 py-1 rounded-full bg-slate-100 text-slate-600">{{ $members->count() }}</span>
        </div>

        <table class="w-full text-sm">
            <thead class="bg-slate-50/80">
                <tr>
                    <th class="text-left px-6 py-4 font-semibold text-slate-500 uppercase tracking-wider text-xs">Member</th>
                    <th class="text-left px-6 py-4 font-semibold text-slate-500 uppercase tracking-wider text-xs">Role</th>
                    <th class="text-left px-6 py-4 font-semibold text-slate-500 uppercase tracking-wider text-xs">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($members as $member)
                <tr class="hover:bg-slate-50/50 transition-colors">
                    <td class="px-6 py-4">
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 rounded-full bg-gradient-to-br from-primary to-primary-container flex items-center justify-center text-white text-xs font-bold flex-shrink-0">
                                {{ strtoupper(substr($member->name, 0, 2)) }}
                            </div>
                            <div class="min-w-0">
                                <div class="font-semibold text-slate-700 truncate">{{ $member->name }}</div>
                                <div class="text-xs text-slate-400 truncate">{{ $member->email }}</div>
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4 text-slate-600">
                        {{ $member->roles->pluck('name')->join(', ') ?: '—' }}
                    </td>
                    <td class="px-6 py-4">
                        @php
                            $statusColors = ['active' => 'bg-emerald-100 text-emerald-700', 'probation' => 'bg-amber-100 text-amber-700', 'inactive' => 'bg-slate-100 text-slate-600', 'terminated' => 'bg-red-100 text-red-700'];
                        @endphp
                        <span class="text-[10px] font-bold uppercase tracking-widest px-2 py-0.5 rounded-full {{ $statusColors[$member->status] ?? 'bg-slate-100 text-slate-600' }}">
                            {{ $member->status ?? 'active' }}
                        </span>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="3" class="px-6 py-14 text-center text-slate-400">
                        <span class="material-symbols-outlined text-4xl block mb-2">group</span>
                        No members yet.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

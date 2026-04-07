<div>
    <div class="mb-8">
        <h2 class="text-3xl font-extrabold text-slate-900 font-headline">Audit Logs</h2>
        <p class="text-slate-500 text-sm mt-1">System activity and change history</p>
    </div>

    <div class="glass-card rounded-[2rem] overflow-hidden">
        <div class="p-6 border-b border-slate-100 flex flex-wrap gap-3">
            <input wire:model.live.debounce.300ms="search" type="text" placeholder="Search logs..."
                class="px-4 py-2 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-primary/20 focus:outline-none flex-1 min-w-48"/>
            <select wire:model.live="actionFilter" class="px-4 py-2 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-primary/20 focus:outline-none">
                <option value="">All Actions</option>
                @foreach($actions as $action)
                <option value="{{ $action }}">{{ ucfirst($action) }}</option>
                @endforeach
            </select>
        </div>

        <table class="w-full text-sm">
            <thead class="bg-slate-50/80">
                <tr>
                    <th class="text-left px-6 py-4 font-semibold text-slate-500 uppercase tracking-wider text-xs">Time</th>
                    <th class="text-left px-6 py-4 font-semibold text-slate-500 uppercase tracking-wider text-xs">User</th>
                    <th class="text-left px-6 py-4 font-semibold text-slate-500 uppercase tracking-wider text-xs">Action</th>
                    <th class="text-left px-6 py-4 font-semibold text-slate-500 uppercase tracking-wider text-xs">Description</th>
                    <th class="text-left px-6 py-4 font-semibold text-slate-500 uppercase tracking-wider text-xs">IP</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($logs as $log)
                @php
                    $actionColors = ['create'=>'bg-green-100 text-green-700','update'=>'bg-blue-100 text-blue-700','delete'=>'bg-red-100 text-red-700','login'=>'bg-primary/10 text-primary','logout'=>'bg-slate-100 text-slate-600'];
                    $color = $actionColors[$log->action] ?? 'bg-slate-100 text-slate-600';
                @endphp
                <tr class="hover:bg-slate-50/50 transition-colors">
                    <td class="px-6 py-4 text-slate-500 text-xs whitespace-nowrap">{{ $log->created_at->format('M d, Y H:i') }}</td>
                    <td class="px-6 py-4 font-semibold text-slate-700">{{ $log->user?->name ?? 'System' }}</td>
                    <td class="px-6 py-4">
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-widest {{ $color }}">{{ $log->action }}</span>
                    </td>
                    <td class="px-6 py-4 text-slate-600 max-w-xs truncate">{{ $log->description }}</td>
                    <td class="px-6 py-4 text-slate-400 text-xs">{{ $log->ip_address }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="px-6 py-16 text-center text-slate-400">
                        <span class="material-symbols-outlined text-4xl block mb-2">history</span>
                        No audit logs found.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>

        <div class="p-4 border-t border-slate-100">{{ $logs->links() }}</div>
    </div>
</div>

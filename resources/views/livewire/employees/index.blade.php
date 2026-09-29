<div>
    {{-- Flash Messages --}}
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

    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
        <div>
            <h2 class="text-2xl md:text-3xl font-extrabold text-slate-900 font-headline">Employees</h2>
            <p class="text-slate-500 text-sm mt-1">Manage your team members</p>
        </div>
        <a href="{{ route('employees.create') }}" wire:navigate
            class="bg-gradient-to-br from-primary to-primary-container text-on-primary px-5 py-2.5 rounded-xl font-bold font-headline shadow-lg shadow-primary/20 hover:opacity-90 transition-opacity flex items-center justify-center gap-2 w-full sm:w-auto">
            <span class="material-symbols-outlined text-sm">add</span> Add Employee
        </a>
    </div>

    {{-- Filters --}}
    <div class="glass-card rounded-[1.5rem] p-4 mb-6">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
            <input wire:model.live.debounce.300ms="search" type="text" placeholder="Search by name, email, ID..."
                class="px-4 py-2 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-primary/20 focus:outline-none"/>
            <select wire:model.live="statusFilter" class="px-4 py-2 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-primary/20 focus:outline-none">
                <option value="">All Status</option>
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
                <option value="terminated">Terminated</option>
                <option value="probation">Probation</option>
            </select>
            <select wire:model.live="departmentFilter" class="px-4 py-2 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-primary/20 focus:outline-none">
                <option value="">All Departments</option>
                @foreach($departments as $dept)
                    <option value="{{ $dept }}">{{ $dept }}</option>
                @endforeach
            </select>
            <select wire:model.live="roleFilter" class="px-4 py-2 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-primary/20 focus:outline-none">
                <option value="">All Roles</option>
                <option value="Employee">Employee</option>
                <option value="Manager">Manager</option>
                <option value="HR Admin">HR Admin</option>
                <option value="Super Admin">Super Admin</option>
            </select>
        </div>
    </div>

    {{-- Desktop Table --}}
    <div class="hidden md:block glass-card rounded-[2rem] overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-slate-50/80">
                    <tr>
                        <th class="text-left px-6 py-4 font-semibold text-slate-500 uppercase tracking-wider text-xs">Employee</th>
                        <th class="text-left px-6 py-4 font-semibold text-slate-500 uppercase tracking-wider text-xs">ID</th>
                        <th class="text-left px-6 py-4 font-semibold text-slate-500 uppercase tracking-wider text-xs">Department</th>
                        <th class="text-left px-6 py-4 font-semibold text-slate-500 uppercase tracking-wider text-xs">Position</th>
                        <th class="text-left px-6 py-4 font-semibold text-slate-500 uppercase tracking-wider text-xs">Status</th>
                        <th class="text-left px-6 py-4 font-semibold text-slate-500 uppercase tracking-wider text-xs">Role</th>
                        <th class="px-6 py-4"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($employees as $employee)
                    <tr class="hover:bg-slate-50/50 transition-colors">
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-full bg-gradient-to-br from-primary to-primary-container flex items-center justify-center text-white font-bold text-sm flex-shrink-0">
                                    {{ strtoupper(substr($employee->name, 0, 2)) }}
                                </div>
                                <div>
                                    <a href="{{ route('employees.show', $employee) }}" wire:navigate
                                        class="font-bold text-slate-800 hover:text-primary transition-colors">{{ $employee->name }}</a>
                                    <p class="text-xs text-slate-400">{{ $employee->email }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 text-slate-500">{{ $employee->employee_id }}</td>
                        <td class="px-6 py-4 text-slate-500">{{ $employee->department ?? '-' }}</td>
                        <td class="px-6 py-4 text-slate-500">{{ $employee->position ?? '-' }}</td>
                        <td class="px-6 py-4">
                            <span class="px-2 py-0.5 rounded-full text-xs font-bold
                                @if($employee->status === 'active') bg-green-100 text-green-700
                                @elseif($employee->status === 'inactive') bg-slate-100 text-slate-600
                                @elseif($employee->status === 'terminated') bg-red-100 text-red-700
                                @else bg-amber-100 text-amber-700 @endif">
                                {{ ucfirst($employee->status) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-slate-500">
                            {{ $employee->roles->first()?->name ?? '-' }}
                        </td>
                        <td class="px-6 py-4 text-right">
                            <div class="flex items-center justify-end gap-2">
                                <a href="{{ route('employees.show', $employee) }}" wire:navigate
                                    class="p-2 rounded-xl text-slate-400 hover:text-primary hover:bg-slate-50 transition-colors" title="View profile">
                                    <span class="material-symbols-outlined text-sm">person</span>
                                </a>
                                <a href="{{ route('employees.edit', $employee) }}" wire:navigate
                                    class="p-2 rounded-xl text-slate-400 hover:text-primary hover:bg-slate-50 transition-colors" title="Edit">
                                    <span class="material-symbols-outlined text-sm">edit</span>
                                </a>
                                @if($employee->id !== auth()->id())
                                <button wire:click="deleteEmployee({{ $employee->id }})"
                                    wire:confirm="Are you sure you want to delete this employee?"
                                    class="p-2 rounded-xl text-slate-400 hover:text-error hover:bg-slate-50 transition-colors" title="Delete">
                                    <span class="material-symbols-outlined text-sm">delete</span>
                                </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-6 py-16 text-center text-slate-400">
                            <span class="material-symbols-outlined text-5xl block mb-3">groups</span>
                            <p>No employees found.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Mobile Cards --}}
    <div class="md:hidden space-y-3">
        @forelse($employees as $employee)
        <div class="glass-card rounded-[1.5rem] p-4">
            <div class="flex items-start justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 rounded-full bg-gradient-to-br from-primary to-primary-container flex items-center justify-center text-white font-bold flex-shrink-0">
                        {{ strtoupper(substr($employee->name, 0, 2)) }}
                    </div>
                    <div>
                        <p class="font-bold text-slate-800">{{ $employee->name }}</p>
                        <p class="text-xs text-slate-400">{{ $employee->email }}</p>
                    </div>
                </div>
                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold
                    @if($employee->status === 'active') bg-green-100 text-green-700
                    @elseif($employee->status === 'inactive') bg-slate-100 text-slate-600
                    @elseif($employee->status === 'terminated') bg-red-100 text-red-700
                    @else bg-amber-100 text-amber-700 @endif">
                    {{ ucfirst($employee->status) }}
                </span>
            </div>
            <div class="mt-3 grid grid-cols-2 gap-2 text-xs">
                <div class="bg-slate-50 rounded-lg p-2">
                    <p class="text-slate-400">ID</p>
                    <p class="font-semibold text-slate-700">{{ $employee->employee_id ?? '-' }}</p>
                </div>
                <div class="bg-slate-50 rounded-lg p-2">
                    <p class="text-slate-400">Department</p>
                    <p class="font-semibold text-slate-700">{{ $employee->department ?? '-' }}</p>
                </div>
                <div class="bg-slate-50 rounded-lg p-2">
                    <p class="text-slate-400">Position</p>
                    <p class="font-semibold text-slate-700">{{ $employee->position ?? '-' }}</p>
                </div>
                <div class="bg-slate-50 rounded-lg p-2">
                    <p class="text-slate-400">Role</p>
                    <p class="font-semibold text-slate-700">{{ $employee->roles->first()?->name ?? '-' }}</p>
                </div>
            </div>
            <div class="mt-3 flex items-center gap-2">
                <a href="{{ route('employees.edit', $employee) }}" wire:navigate
                    class="flex-1 text-center text-xs font-bold py-2 rounded-xl bg-primary/10 text-primary hover:bg-primary/20 transition-colors">
                    Edit
                </a>
                @if($employee->id !== auth()->id())
                <button wire:click="deleteEmployee({{ $employee->id }})"
                    wire:confirm="Are you sure you want to delete this employee?"
                    class="flex-1 text-center text-xs font-bold py-2 rounded-xl bg-red-50 text-red-600 hover:bg-red-100 transition-colors">
                    Delete
                </button>
                @endif
            </div>
        </div>
        @empty
        <div class="glass-card rounded-[2rem] py-16 text-center text-slate-400">
            <span class="material-symbols-outlined text-5xl block mb-3">groups</span>
            <p>No employees found.</p>
        </div>
        @endforelse
    </div>

    <div class="mt-4">{{ $employees->links() }}</div>
</div>

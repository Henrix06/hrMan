<div>
    {{-- Header --}}
    <div class="bg-white rounded-lg shadow p-6 mb-6">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div class="flex items-center gap-4">
                <div class="w-14 h-14 rounded-full bg-gradient-to-br from-blue-500 to-blue-700 flex items-center justify-center text-white text-lg font-bold flex-shrink-0">
                    {{ strtoupper(substr($employee->name, 0, 2)) }}
                </div>
                <div class="min-w-0">
                    <h2 class="text-2xl font-bold text-gray-800 truncate">{{ $employee->name }}</h2>
                    <p class="text-gray-500 text-sm">
                        {{ $employee->position ?: 'Employee' }}
                        @if($employee->employee_id) · {{ $employee->employee_id }} @endif
                    </p>
                </div>
            </div>

            <div class="flex items-center gap-3">
                @php
                    $statusColors = ['active' => 'bg-green-100 text-green-700', 'probation' => 'bg-amber-100 text-amber-700', 'inactive' => 'bg-gray-100 text-gray-600', 'terminated' => 'bg-red-100 text-red-700'];
                @endphp
                <span class="text-[11px] font-bold uppercase tracking-widest px-3 py-1 rounded-full {{ $statusColors[$employee->status] ?? 'bg-gray-100 text-gray-600' }}">
                    {{ $employee->status }}
                </span>

                @can('edit employee')
                <a href="{{ route('employees.edit', $employee->id) }}" wire:navigate
                    class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white px-5 py-2 rounded-md text-sm font-medium">
                    <span class="material-symbols-outlined text-[18px]">edit</span>
                    Edit Record
                </a>
                @endcan

                <a href="{{ route('employees.index') }}" wire:navigate class="text-gray-500 hover:text-gray-800 text-sm">
                    ← Back
                </a>
            </div>
        </div>
    </div>

    {{-- Sections --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        @foreach($sections as $sectionTitle => $fields)
        <div class="bg-white rounded-lg shadow overflow-hidden {{ $sectionTitle === 'Employment' ? 'lg:col-span-2' : '' }}">
            <div class="px-6 py-4 border-b border-gray-100">
                <h3 class="text-base font-semibold text-gray-800">{{ $sectionTitle }}</h3>
            </div>

            <dl class="divide-y divide-gray-50">
                @foreach($fields as $fieldLabel => $value)
                <div class="flex items-start gap-4 px-6 py-3">
                    <dt class="w-40 flex-shrink-0 text-sm text-gray-500">{{ $fieldLabel }}</dt>
                    <dd class="text-sm font-medium text-gray-800 break-words min-w-0">{{ $value }}</dd>
                </div>
                @endforeach
            </dl>
        </div>
        @endforeach
    </div>
</div>

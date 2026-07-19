<div>
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

    <div class="mb-6">
        <h2 class="text-2xl md:text-3xl font-extrabold text-slate-900 font-headline">Run Payroll</h2>
        <p class="text-slate-500 text-sm mt-1">Process payroll for selected employees</p>
    </div>

    <form wire:submit="processPayroll" class="space-y-6">
        {{-- Pay Period and Date --}}
        <div class="glass-card rounded-[2rem] p-6">
            <h3 class="text-lg font-bold font-headline text-slate-900 mb-4">Payroll Settings</h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1">Pay Period</label>
                    <input type="month" wire:model="payPeriod"
                        class="w-full px-4 py-2 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-primary/20 focus:outline-none @error('payPeriod') border-error @enderror">
                    @error('payPeriod') <p class="text-error text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1">Pay Date</label>
                    <input type="date" wire:model="payDate"
                        class="w-full px-4 py-2 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-primary/20 focus:outline-none @error('payDate') border-error @enderror">
                    @error('payDate') <p class="text-error text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1">Tax Rate (%)</label>
                    <input type="number" wire:model="taxRate" min="0" max="100" step="0.1"
                        class="w-full px-4 py-2 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-primary/20 focus:outline-none @error('taxRate') border-error @enderror">
                    @error('taxRate') <p class="text-error text-xs mt-1">{{ $message }}</p> @enderror
                </div>
            </div>
        </div>

        {{-- Employee Selection --}}
        <div class="glass-card rounded-[2rem] p-6">
            <h3 class="text-lg font-bold font-headline text-slate-900 mb-4">Select Employees</h3>
            <div class="space-y-2">
                @foreach($this->employees as $employee)
                <label class="flex items-center p-3 rounded-xl border border-slate-100 hover:bg-slate-50 cursor-pointer transition-colors">
                    <input type="checkbox" wire:model="selectedEmployees" value="{{ $employee->id }}"
                        class="rounded border-slate-300 text-primary focus:ring-primary/20">
                    <div class="ml-3 flex-1 min-w-0">
                        <div class="font-bold text-slate-800">{{ $employee->name }}</div>
                        <div class="text-xs text-slate-400 truncate">{{ $employee->email }} · {{ $employee->position }}</div>
                    </div>
                    <div class="text-sm font-semibold text-slate-600 flex-shrink-0">
                        ${{ number_format($employee->base_salary, 2) }}
                    </div>
                </label>
                @endforeach
            </div>
            @error('selectedEmployees') <p class="text-error text-xs mt-1">{{ $message }}</p> @enderror
        </div>

        {{-- Additional Pay Components --}}
        @if(count($selectedEmployees) > 0)
        <div class="glass-card rounded-[2rem] p-6">
            <h3 class="text-lg font-bold font-headline text-slate-900 mb-4">Additional Pay Components</h3>
            <div class="space-y-4">
                @foreach($selectedEmployees as $employeeId)
                @php $employee = $this->employees->find($employeeId); @endphp
                @if($employee)
                <div class="border-t border-slate-100 pt-4">
                    <h4 class="font-bold text-slate-800 mb-3">{{ $employee->name }}</h4>
                    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-slate-500 mb-1">Allowances</label>
                            <input type="number" wire:model="allowances.{{ $employeeId }}" min="0" step="0.01"
                                class="w-full px-3 py-2 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-primary/20 focus:outline-none"/>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-500 mb-1">Overtime</label>
                            <input type="number" wire:model="overtimePay.{{ $employeeId }}" min="0" step="0.01"
                                class="w-full px-3 py-2 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-primary/20 focus:outline-none"/>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-500 mb-1">Bonus</label>
                            <input type="number" wire:model="bonus.{{ $employeeId }}" min="0" step="0.01"
                                class="w-full px-3 py-2 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-primary/20 focus:outline-none"/>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-500 mb-1">Insurance</label>
                            <input type="number" wire:model="insuranceDeduction.{{ $employeeId }}" min="0" step="0.01"
                                class="w-full px-3 py-2 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-primary/20 focus:outline-none"/>
                        </div>
                    </div>
                </div>
                @endif
                @endforeach
            </div>
        </div>
        @endif

        {{-- Submit --}}
        <div class="flex items-center justify-end">
            <button type="submit"
                class="bg-gradient-to-br from-primary to-primary-container text-on-primary px-6 py-3 rounded-xl font-bold font-headline shadow-lg shadow-primary/20 hover:opacity-90 transition-opacity flex items-center gap-2">
                <span class="material-symbols-outlined text-sm">payments</span>
                Process Payroll
            </button>
        </div>
    </form>
</div>

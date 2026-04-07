<div>
    <!-- Flash Messages -->
    @if (session()->has('message'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
            {{ session('message') }}
        </div>
    @endif

    @if (session()->has('error'))
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
            {{ session('error') }}
        </div>
    @endif

    <h2 class="text-2xl font-bold text-gray-800 mb-6">Run Payroll</h2>

    <form wire:submit="processPayroll" class="space-y-6">
        <!-- Pay Period and Date -->
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Payroll Settings</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Pay Period</label>
                    <input type="month" wire:model="payPeriod" 
                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 @error('payPeriod') border-red-500 @enderror">
                    @error('payPeriod') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Pay Date</label>
                    <input type="date" wire:model="payDate" 
                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 @error('payDate') border-red-500 @enderror">
                    @error('payDate') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Tax Rate (%)</label>
                    <input type="number" wire:model="taxRate" min="0" max="100" step="0.1"
                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 @error('taxRate') border-red-500 @enderror">
                    @error('taxRate') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                </div>
            </div>
        </div>

        <!-- Employee Selection -->
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Select Employees</h3>
            <div class="space-y-3">
                @foreach($this->employees as $employee)
                <label class="flex items-center p-3 border rounded-lg hover:bg-gray-50 cursor-pointer">
                    <input type="checkbox" wire:model="selectedEmployees" value="{{ $employee->id }}"
                        class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    <div class="ml-3 flex-1">
                        <div class="font-medium text-gray-900">{{ $employee->name }}</div>
                        <div class="text-sm text-gray-500">{{ $employee->email }} - {{ $employee->position }}</div>
                    </div>
                    <div class="text-sm text-gray-600">
                        Base: ${{ number_format($employee->base_salary, 2) }}
                    </div>
                </label>
                @endforeach
            </div>
            @error('selectedEmployees') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
        </div>

        <!-- Additional Pay Components -->
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Additional Pay Components (Optional)</h3>
            <div class="space-y-4">
                @foreach($selectedEmployees as $employeeId)
                @php
                    $employee = $this->employees->find($employeeId);
                @endphp
                @if($employee)
                <div class="border-t pt-4">
                    <h4 class="font-medium text-gray-800 mb-2">{{ $employee->name }}</h4>
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div>
                            <label class="block text-sm text-gray-700 mb-1">Allowances</label>
                            <input type="number" wire:model="allowances.{{ $employeeId }}" min="0" step="0.01"
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        </div>
                        <div>
                            <label class="block text-sm text-gray-700 mb-1">Overtime Pay</label>
                            <input type="number" wire:model="overtimePay.{{ $employeeId }}" min="0" step="0.01"
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        </div>
                        <div>
                            <label class="block text-sm text-gray-700 mb-1">Bonus</label>
                            <input type="number" wire:model="bonus.{{ $employeeId }}" min="0" step="0.01"
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        </div>
                        <div>
                            <label class="block text-sm text-gray-700 mb-1">Insurance</label>
                            <input type="number" wire:model="insuranceDeduction.{{ $employeeId }}" min="0" step="0.01"
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        </div>
                    </div>
                </div>
                @endif
                @endforeach
            </div>
        </div>

        <!-- Submit -->
        <div class="flex items-center justify-end">
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-md font-medium">
                Process Payroll
            </button>
        </div>
    </form>
</div>

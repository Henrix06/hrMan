<div>
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

    @if($payslip)
    <div class="flex items-center justify-between mb-6">
        <h2 class="text-2xl font-bold text-gray-800">Payslip Details</h2>
        <a href="{{ route('payslips.index') }}" wire:navigate class="text-gray-600 hover:text-gray-800">
            ← Back to Payslips
        </a>
    </div>

    <div class="bg-white rounded-lg shadow p-6 max-w-4xl">
        <!-- Header -->
        <div class="border-b pb-4 mb-4">
            <div class="flex justify-between items-start">
                <div>
                    <h3 class="text-xl font-bold text-gray-800">{{ auth()->user()->name }}</h3>
                    <p class="text-gray-600">{{ $payslip->user->position ?? 'Employee' }}</p>
                    <p class="text-gray-600">{{ $payslip->user->employee_id ?? 'N/A' }}</p>
                </div>
                <div class="text-right">
                    <h4 class="font-bold text-lg">Payslip</h4>
                    <p class="text-gray-600">{{ $payslip->payslip_number }}</p>
                    <p class="text-gray-600">Pay Period: {{ $payslip->pay_period }}</p>
                </div>
            </div>
        </div>

        <!-- Earnings -->
        <div class="mb-6">
            <h4 class="font-semibold text-gray-800 mb-2">Earnings</h4>
            <table class="w-full">
                <tr class="border-b">
                    <td class="py-2 text-gray-600">Base Salary</td>
                    <td class="py-2 text-right">${{ number_format($payslip->payrollDetail->base_salary ?? 0, 2) }}</td>
                </tr>
                @if($payslip->payrollDetail)
                <tr class="border-b">
                    <td class="py-2 text-gray-600">Allowances</td>
                    <td class="py-2 text-right">${{ number_format($payslip->payrollDetail->allowances ?? 0, 2) }}</td>
                </tr>
                <tr class="border-b">
                    <td class="py-2 text-gray-600">Overtime Pay</td>
                    <td class="py-2 text-right">${{ number_format($payslip->payrollDetail->overtime_pay ?? 0, 2) }}</td>
                </tr>
                <tr class="border-b">
                    <td class="py-2 text-gray-600">Bonus</td>
                    <td class="py-2 text-right">${{ number_format($payslip->payrollDetail->bonus ?? 0, 2) }}</td>
                </tr>
                @endif
                <tr class="font-semibold">
                    <td class="py-2 text-gray-800">Gross Pay</td>
                    <td class="py-2 text-right">${{ number_format($payslip->gross_pay, 2) }}</td>
                </tr>
            </table>
        </div>

        <!-- Deductions -->
        <div class="mb-6">
            <h4 class="font-semibold text-gray-800 mb-2">Deductions</h4>
            <table class="w-full">
                @if($payslip->payrollDetail)
                <tr class="border-b">
                    <td class="py-2 text-gray-600">Tax Deduction</td>
                    <td class="py-2 text-right">${{ number_format($payslip->payrollDetail->tax_deduction ?? 0, 2) }}</td>
                </tr>
                <tr class="border-b">
                    <td class="py-2 text-gray-600">Insurance</td>
                    <td class="py-2 text-right">${{ number_format($payslip->payrollDetail->insurance_deduction ?? 0, 2) }}</td>
                </tr>
                <tr class="border-b">
                    <td class="py-2 text-gray-600">Other Deductions</td>
                    <td class="py-2 text-right">${{ number_format($payslip->payrollDetail->other_deductions ?? 0, 2) }}</td>
                </tr>
                @endif
                <tr class="font-semibold">
                    <td class="py-2 text-gray-800">Total Deductions</td>
                    <td class="py-2 text-right">${{ number_format($payslip->total_deductions, 2) }}</td>
                </tr>
            </table>
        </div>

        <!-- Net Pay -->
        <div class="border-t-2 border-gray-800 pt-4">
            <div class="flex justify-between items-center">
                <span class="text-xl font-bold text-gray-800">Net Pay</span>
                <span class="text-2xl font-bold text-green-600">${{ number_format($payslip->net_pay, 2) }}</span>
            </div>
        </div>

        <!-- Footer -->
        <div class="mt-6 pt-4 border-t text-sm text-gray-600">
            <div class="flex justify-between">
                <span>Issue Date: {{ $payslip->issue_date->format('M d, Y') }}</span>
                <span>Pay Date: {{ $payslip->pay_date->format('M d, Y') }}</span>
            </div>
        </div>

        <!-- Actions -->
        <div class="mt-6 flex justify-end space-x-3">
            <button wire:click="downloadPayslip" class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-md">
                Download PDF
            </button>
            @role('HR Admin|Super Admin')
            @if(!$payslip->sent_to_employee)
            <button wire:click="sendToEmployee" wire:confirm="Send this payslip to the employee?" 
                class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md">
                Send to Employee
            </button>
            @endif
            @endrole
        </div>
    </div>
    @else
    <div class="flex flex-col items-center justify-center py-16 text-slate-400">
        <span class="material-symbols-outlined text-4xl mb-3">receipt_long</span>
        <p class="text-sm">No payslip selected.</p>
        <a href="{{ route('payslips.index') }}" wire:navigate class="mt-4 text-primary text-sm font-bold hover:underline">← Back to Payslips</a>
    </div>
    @endif
</div>

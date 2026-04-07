<?php

namespace App\Livewire\Payroll;

use App\Actions\Payroll\ProcessPayroll;
use App\Models\User;
use App\Support\Payroll\PayrollCalculator;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class RunPayroll extends Component
{
    public $payPeriod = '';
    public $payDate = '';
    public $selectedEmployees = [];
    public $allowances = [];
    public $overtimePay = [];
    public $bonus = [];
    public $taxRate = 10;
    public $insuranceDeduction = [];

    public function mount(): void
    {
        $this->payPeriod = date('Y-m');
        $this->payDate = date('Y-m-d');
    }

    protected function rules(): array
    {
        return [
            'payPeriod' => 'required|date_format:Y-m',
            'payDate' => 'required|date|after_or_equal:today',
            'selectedEmployees' => 'required|array|min:1',
            'taxRate' => 'required|numeric|min:0|max:100',
        ];
    }

    public function calculatePayroll($employeeId): array
    {
        return app(PayrollCalculator::class)->calculate(
            User::findOrFail($employeeId),
            [
                'allowances' => $this->allowances[$employeeId] ?? 0,
                'overtime_pay' => $this->overtimePay[$employeeId] ?? 0,
                'bonus' => $this->bonus[$employeeId] ?? 0,
                'insurance_deduction' => $this->insuranceDeduction[$employeeId] ?? 0,
                'tax_rate' => $this->taxRate,
            ],
        );
    }

    public function processPayroll(): void
    {
        $this->validate();

        $user = Auth::user();
        if (!$user->can('run payroll')) {
            session()->flash('error', 'You do not have permission to run payroll.');
            return;
        }

        try {
            $processedCount = app(ProcessPayroll::class)->execute(
                payPeriod: $this->payPeriod,
                payDate: $this->payDate,
                selectedEmployees: $this->selectedEmployees,
                taxRate: (float) $this->taxRate,
                allowances: $this->allowances,
                overtimePay: $this->overtimePay,
                bonus: $this->bonus,
                insuranceDeduction: $this->insuranceDeduction,
            );

            session()->flash('message', "Payroll processed successfully for {$processedCount} employee(s).");
            $this->reset(['selectedEmployees', 'allowances', 'overtimePay', 'bonus', 'insuranceDeduction']);
        } catch (\Throwable $exception) {
            session()->flash('error', 'Failed to process payroll: '.$exception->getMessage());
        }
    }

    public function getEmployeesProperty()
    {
        return \Illuminate\Support\Facades\Cache::remember('payroll_active_employees', 120, fn() =>
            User::where('status', 'active')
                ->whereNotNull('base_salary')
                ->with('roles:id,name')
                ->get(['id', 'name', 'employee_id', 'department', 'position', 'base_salary'])
        );
    }

    public function render()
    {
        return view('livewire.payroll.run');
    }
}

<?php

namespace App\Actions\Payroll;

use App\Models\PayrollDetail;
use App\Models\Payslip;
use App\Models\User;
use App\Support\Payroll\PayrollCalculator;
use Illuminate\Support\Facades\DB;

class ProcessPayroll
{
    public function __construct(
        private readonly PayrollCalculator $payrollCalculator,
    ) {
    }

    public function execute(
        string $payPeriod,
        string $payDate,
        array $selectedEmployees,
        float $taxRate,
        array $allowances = [],
        array $overtimePay = [],
        array $bonus = [],
        array $insuranceDeduction = [],
    ): int {
        return DB::transaction(function () use (
            $allowances,
            $bonus,
            $insuranceDeduction,
            $overtimePay,
            $payDate,
            $payPeriod,
            $selectedEmployees,
            $taxRate,
        ) {
            $processedCount = 0;

            foreach ($selectedEmployees as $employeeId) {
                $employee = User::findOrFail($employeeId);

                $existingPayroll = PayrollDetail::query()
                    ->where('user_id', $employeeId)
                    ->where('pay_period', $payPeriod)
                    ->first();

                if ($existingPayroll) {
                    continue;
                }

                $payrollData = $this->payrollCalculator->calculate($employee, [
                    'allowances' => $allowances[$employeeId] ?? 0,
                    'overtime_pay' => $overtimePay[$employeeId] ?? 0,
                    'bonus' => $bonus[$employeeId] ?? 0,
                    'insurance_deduction' => $insuranceDeduction[$employeeId] ?? 0,
                    'tax_rate' => $taxRate,
                ]);

                $payrollDetail = PayrollDetail::create([
                    'user_id' => $employeeId,
                    'pay_period' => $payPeriod,
                    'pay_date' => $payDate,
                    'base_salary' => $payrollData['base_salary'],
                    'allowances' => $payrollData['allowances'],
                    'overtime_pay' => $payrollData['overtime_pay'],
                    'bonus' => $payrollData['bonus'],
                    'tax_deduction' => $payrollData['tax_deduction'],
                    'insurance_deduction' => $payrollData['insurance_deduction'],
                    'other_deductions' => $payrollData['other_deductions'],
                    'net_salary' => $payrollData['net_salary'],
                    'status' => 'processed',
                ]);

                Payslip::create([
                    'payroll_detail_id' => $payrollDetail->id,
                    'user_id' => $employeeId,
                    'payslip_number' => Payslip::generatePayslipNumber(),
                    'pay_period' => $payPeriod,
                    'issue_date' => now(),
                    'pay_date' => $payDate,
                    'gross_pay' => $payrollData['gross_pay'],
                    'total_deductions' => $payrollData['total_deductions'],
                    'net_pay' => $payrollData['net_salary'],
                ]);

                $processedCount++;
            }

            return $processedCount;
        });
    }
}

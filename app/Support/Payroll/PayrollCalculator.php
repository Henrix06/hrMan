<?php

namespace App\Support\Payroll;

use App\Models\User;

class PayrollCalculator
{
    public function calculate(User $employee, array $inputs): array
    {
        $baseSalary = (float) ($employee->base_salary ?? 0);
        $allowance = (float) ($inputs['allowances'] ?? 0);
        $overtimePay = (float) ($inputs['overtime_pay'] ?? 0);
        $bonus = (float) ($inputs['bonus'] ?? 0);
        $insuranceDeduction = (float) ($inputs['insurance_deduction'] ?? 0);
        $taxRate = (float) ($inputs['tax_rate'] ?? 0);

        $grossPay = $baseSalary + $allowance + $overtimePay + $bonus;
        $taxDeduction = ($grossPay * $taxRate) / 100;
        $otherDeductions = 0.0;
        $totalDeductions = $taxDeduction + $insuranceDeduction + $otherDeductions;
        $netSalary = $grossPay - $totalDeductions;

        return [
            'base_salary' => $baseSalary,
            'allowances' => $allowance,
            'overtime_pay' => $overtimePay,
            'bonus' => $bonus,
            'gross_pay' => $grossPay,
            'tax_deduction' => $taxDeduction,
            'insurance_deduction' => $insuranceDeduction,
            'other_deductions' => $otherDeductions,
            'total_deductions' => $totalDeductions,
            'net_salary' => $netSalary,
        ];
    }
}

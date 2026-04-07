<?php

namespace Tests\Unit\Support\Payroll;

use App\Models\User;
use App\Support\Payroll\PayrollCalculator;
use PHPUnit\Framework\TestCase;

class PayrollCalculatorTest extends TestCase
{
    public function test_it_builds_a_consistent_payroll_breakdown(): void
    {
        $calculator = new PayrollCalculator();
        $employee = new User([
            'base_salary' => 1000,
        ]);

        $payroll = $calculator->calculate($employee, [
            'allowances' => 100,
            'overtime_pay' => 50,
            'bonus' => 25,
            'insurance_deduction' => 30,
            'tax_rate' => 10,
        ]);

        $this->assertSame(1175.0, $payroll['gross_pay']);
        $this->assertSame(117.5, $payroll['tax_deduction']);
        $this->assertSame(0.0, $payroll['other_deductions']);
        $this->assertSame(147.5, $payroll['total_deductions']);
        $this->assertSame(1027.5, $payroll['net_salary']);
    }
}

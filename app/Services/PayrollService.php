<?php

namespace App\Services;

use App\Jobs\ProcessPayrollJob;
use App\Jobs\SendPayslipEmailJob;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class PayrollService
{
    private const CHUNK_SIZE = 100;

    /**
     * Dispatch payroll jobs in chunks to avoid memory exhaustion on large employee sets.
     */
    public function dispatchBulkPayroll(
        string $payPeriod,
        string $payDate,
        float $taxRate,
        array $allowances = [],
        array $overtimePay = [],
        array $bonus = [],
        array $insuranceDeduction = [],
    ): int {
        $dispatched = 0;

        User::query()
            ->where('status', 'active')
            ->whereNotNull('base_salary')
            ->select('id')
            ->chunk(self::CHUNK_SIZE, function ($employees) use (
                $payPeriod, $payDate, $taxRate,
                $allowances, $overtimePay, $bonus, $insuranceDeduction,
                &$dispatched
            ) {
                $ids = $employees->pluck('id')->all();

                ProcessPayrollJob::dispatch(
                    payPeriod: $payPeriod,
                    payDate: $payDate,
                    selectedEmployees: $ids,
                    taxRate: $taxRate,
                    allowances: array_intersect_key($allowances, array_flip($ids)),
                    overtimePay: array_intersect_key($overtimePay, array_flip($ids)),
                    bonus: array_intersect_key($bonus, array_flip($ids)),
                    insuranceDeduction: array_intersect_key($insuranceDeduction, array_flip($ids)),
                )->onQueue('payroll');

                $dispatched += count($ids);
            });

        Log::info('PayrollService: bulk payroll dispatched.', [
            'pay_period' => $payPeriod,
            'total_employees' => $dispatched,
            'chunk_size' => self::CHUNK_SIZE,
        ]);

        return $dispatched;
    }

    /**
     * Send payslip emails in chunks after payroll is processed.
     */
    public function dispatchPayslipEmails(string $payPeriod): void
    {
        User::query()
            ->where('status', 'active')
            ->whereNotNull('email')
            ->cursor()
            ->each(function (User $employee) use ($payPeriod) {
                SendPayslipEmailJob::dispatch($employee, $payPeriod)
                    ->onQueue('notifications');
            });
    }
}

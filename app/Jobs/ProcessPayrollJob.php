<?php

namespace App\Jobs;

use App\Actions\Payroll\ProcessPayroll;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessPayrollJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 300;

    public function __construct(
        public readonly string $payPeriod,
        public readonly string $payDate,
        public readonly array $selectedEmployees,
        public readonly float $taxRate,
        public readonly array $allowances = [],
        public readonly array $overtimePay = [],
        public readonly array $bonus = [],
        public readonly array $insuranceDeduction = [],
    ) {}

    public function handle(ProcessPayroll $action): void
    {
        try {
            $count = $action->execute(
                payPeriod: $this->payPeriod,
                payDate: $this->payDate,
                selectedEmployees: $this->selectedEmployees,
                taxRate: $this->taxRate,
                allowances: $this->allowances,
                overtimePay: $this->overtimePay,
                bonus: $this->bonus,
                insuranceDeduction: $this->insuranceDeduction,
            );

            Log::info('ProcessPayrollJob completed.', [
                'pay_period' => $this->payPeriod,
                'processed' => $count,
            ]);
        } catch (\Throwable $e) {
            Log::error('ProcessPayrollJob failed.', [
                'pay_period' => $this->payPeriod,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}

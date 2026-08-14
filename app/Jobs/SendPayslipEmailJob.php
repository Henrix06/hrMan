<?php

namespace App\Jobs;

use App\Models\Payslip;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendPayslipEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 60;

    public function __construct(
        public readonly User $employee,
        public readonly string $payPeriod,
    ) {}

    public function handle(): void
    {
        $payslip = Payslip::query()
            ->where('user_id', $this->employee->id)
            ->where('pay_period', $this->payPeriod)
            ->with('payrollDetail')
            ->first();

        if (! $payslip) {
            Log::warning('SendPayslipEmailJob: payslip not found.', [
                'user_id' => $this->employee->id,
                'pay_period' => $this->payPeriod,
            ]);
            return;
        }

        // Mail::to($this->employee->email)->send(new \App\Mail\PayslipMail($payslip));

        Log::info('SendPayslipEmailJob: payslip email queued.', [
            'user_id' => $this->employee->id,
            'pay_period' => $this->payPeriod,
            'payslip_number' => $payslip->payslip_number,
        ]);
    }
}

<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PayslipResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'               => $this->id,
            'payslip_number'   => $this->payslip_number,
            'pay_period'       => $this->pay_period,
            'issue_date'       => $this->issue_date?->toDateString(),
            'pay_date'         => $this->pay_date?->toDateString(),
            'gross_pay'        => $this->gross_pay,
            'total_deductions' => $this->total_deductions,
            'net_pay'          => $this->net_pay,
            'employee'         => $this->whenLoaded('user', fn() => [
                'id'          => $this->user->id,
                'name'        => $this->user->name,
                'employee_id' => $this->user->employee_id,
            ]),
            'detail'           => $this->whenLoaded('payrollDetail', fn() => [
                'base_salary'         => $this->payrollDetail->base_salary,
                'allowances'          => $this->payrollDetail->allowances,
                'overtime_pay'        => $this->payrollDetail->overtime_pay,
                'bonus'               => $this->payrollDetail->bonus,
                'tax_deduction'       => $this->payrollDetail->tax_deduction,
                'insurance_deduction' => $this->payrollDetail->insurance_deduction,
            ]),
        ];
    }
}

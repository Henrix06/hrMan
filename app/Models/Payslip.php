<?php

namespace App\Models;

use App\Models\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Payslip extends Model
{
    use HasFactory, BelongsToTenant;

    protected $fillable = [
        'payroll_detail_id', 'user_id', 'payslip_number', 'pay_period', 'issue_date',
        'pay_date', 'gross_pay', 'total_deductions', 'net_pay', 'pdf_path',
        'sent_to_employee', 'sent_at', 'tenant_id',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'issue_date' => 'date',
            'pay_date' => 'date',
            'gross_pay' => 'decimal:2',
            'total_deductions' => 'decimal:2',
            'net_pay' => 'decimal:2',
            'sent_to_employee' => 'boolean',
            'sent_at' => 'datetime',
        ];
    }

    /**
     * Get the payroll detail for the payslip.
     */
    public function payrollDetail(): BelongsTo
    {
        return $this->belongsTo(PayrollDetail::class);
    }

    /**
     * Get the user that owns the payslip.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Generate unique payslip number.
     */
    public static function generatePayslipNumber(): string
    {
        return 'PS-' . date('Y') . '-' . strtoupper(substr(uniqid(), -6));
    }
}

<?php

namespace App\Models;

use App\Models\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PayrollDetail extends Model
{
    use HasFactory, BelongsToTenant;

    protected $fillable = [
        'user_id', 'pay_period', 'pay_date', 'base_salary', 'allowances', 'overtime_pay',
        'bonus', 'tax_deduction', 'insurance_deduction', 'other_deductions', 'net_salary',
        'status', 'notes', 'tenant_id',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'pay_date' => 'date',
            'base_salary' => 'decimal:2',
            'allowances' => 'decimal:2',
            'overtime_pay' => 'decimal:2',
            'bonus' => 'decimal:2',
            'tax_deduction' => 'decimal:2',
            'insurance_deduction' => 'decimal:2',
            'other_deductions' => 'decimal:2',
            'net_salary' => 'decimal:2',
        ];
    }

    /**
     * Get the user that owns the payroll detail.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the payslip for the payroll detail.
     */
    public function payslip(): HasOne
    {
        return $this->hasOne(Payslip::class);
    }

    /**
     * Calculate gross pay.
     */
    public function getGrossPayAttribute(): float
    {
        return round(
            (float) $this->base_salary +
            (float) $this->allowances +
            (float) $this->overtime_pay +
            (float) $this->bonus,
            2
        );
    }

    /**
     * Calculate total deductions.
     */
    public function getTotalDeductionsAttribute(): float
    {
        return round(
            (float) $this->tax_deduction +
            (float) $this->insurance_deduction +
            (float) $this->other_deductions,
            2
        );
    }

    /**
     * Scope for processed payroll.
     */
    public function scopeProcessed($query)
    {
        return $query->where('status', 'processed');
    }

    /**
     * Scope for paid payroll.
     */
    public function scopePaid($query)
    {
        return $query->where('status', 'paid');
    }
}

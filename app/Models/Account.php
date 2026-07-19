<?php

namespace App\Models;

use App\Models\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Account extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'name', 'code', 'type', 'description',
        'balance', 'budget_limit', 'active', 'created_by', 'tenant_id',
    ];

    protected function casts(): array
    {
        return [
            'balance'      => 'decimal:2',
            'budget_limit' => 'decimal:2',
            'active'       => 'boolean',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(AccountTransaction::class);
    }

    public function procurementRequests(): HasMany
    {
        return $this->hasMany(ProcurementRequest::class);
    }

    public function hasAvailableFunds(float $amount): bool
    {
        return (float) $this->balance >= $amount;
    }

    public function isOverBudget(): bool
    {
        return $this->budget_limit !== null && (float) $this->balance < 0;
    }

    public static function generateCode(string $type): string
    {
        $prefix = match ($type) {
            'petty_cash'  => 'PCA',
            'payroll'     => 'PAY',
            'reserve'     => 'RES',
            default       => 'OPS',
        };
        $count = self::where('type', $type)->count() + 1;
        return "ACC-{$prefix}-" . str_pad($count, 3, '0', STR_PAD_LEFT);
    }
}

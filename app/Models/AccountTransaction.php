<?php

namespace App\Models;

use App\Models\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class AccountTransaction extends Model
{
    use BelongsToTenant;

    public $timestamps = false;

    protected $fillable = [
        'account_id', 'created_by', 'reference', 'type', 'amount',
        'balance_after', 'category', 'description',
        'transactable_type', 'transactable_id', 'integrity_hash', 'tenant_id',
    ];

    protected function casts(): array
    {
        return [
            'amount'        => 'decimal:2',
            'balance_after' => 'decimal:2',
            'created_at'    => 'datetime',
        ];
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function transactable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * SHA-256 over the immutable fields — detects any post-write tampering.
     */
    public function computeIntegrityHash(): string
    {
        return hash('sha256', implode('|', [
            $this->account_id,
            $this->reference,
            $this->type,
            $this->amount,
            $this->balance_after,
            $this->created_by,
            $this->created_at?->toIso8601String() ?? now()->toIso8601String(),
        ]));
    }

    public function verifyIntegrity(): bool
    {
        return $this->integrity_hash
            && hash_equals($this->integrity_hash, $this->computeIntegrityHash());
    }

    public static function generateReference(): string
    {
        $year  = date('Y');
        $count = self::whereYear('created_at', $year)->count() + 1;
        return 'TXN-' . $year . '-' . str_pad($count, 5, '0', STR_PAD_LEFT);
    }
}

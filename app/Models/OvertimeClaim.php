<?php

namespace App\Models;

use App\Models\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class OvertimeClaim extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'reference', 'user_id', 'account_id', 'work_date', 'start_time', 'end_time',
        'hours', 'hourly_rate', 'multiplier', 'claim_amount', 'approved_amount',
        'reason', 'status', 'reviewed_by', 'reviewed_at', 'review_notes', 'tenant_id',
    ];

    protected function casts(): array
    {
        return [
            'work_date'       => 'date',
            'hours'           => 'decimal:2',
            'hourly_rate'     => 'decimal:2',
            'multiplier'      => 'decimal:2',
            'claim_amount'    => 'decimal:2',
            'approved_amount' => 'decimal:2',
            'reviewed_at'     => 'datetime',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function transaction(): MorphOne
    {
        return $this->morphOne(AccountTransaction::class, 'transactable');
    }

    public static function generateReference(): string
    {
        $year  = date('Y');
        $count = self::whereYear('created_at', $year)->count() + 1;
        return 'OTC-' . $year . '-' . str_pad($count, 5, '0', STR_PAD_LEFT);
    }

    public function scopePending($q) { return $q->where('status', 'pending'); }
}

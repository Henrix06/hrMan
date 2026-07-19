<?php

namespace App\Models;

use App\Models\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class ProcurementRequest extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'reference', 'requested_by', 'account_id', 'title', 'description',
        'vendor', 'estimated_cost', 'approved_amount', 'actual_cost',
        'category', 'priority', 'status', 'reviewed_by', 'reviewed_at',
        'review_notes', 'required_by', 'receipt_path', 'tenant_id',
    ];

    protected function casts(): array
    {
        return [
            'estimated_cost'  => 'decimal:2',
            'approved_amount' => 'decimal:2',
            'actual_cost'     => 'decimal:2',
            'reviewed_at'     => 'datetime',
            'required_by'     => 'date',
        ];
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
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

    public function isPending(): bool   { return $this->status === 'pending'; }
    public function isApproved(): bool  { return $this->status === 'approved'; }
    public function isDraft(): bool     { return $this->status === 'draft'; }

    public static function generateReference(): string
    {
        $year  = date('Y');
        $count = self::whereYear('created_at', $year)->count() + 1;
        return 'PRQ-' . $year . '-' . str_pad($count, 5, '0', STR_PAD_LEFT);
    }

    public function scopePending($q)  { return $q->where('status', 'pending'); }
    public function scopeApproved($q) { return $q->where('status', 'approved'); }
}

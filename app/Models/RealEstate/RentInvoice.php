<?php

namespace App\Models\RealEstate;

use App\Models\AccountTransaction;
use App\Models\Traits\HasRealEstateReference;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RentInvoice extends Model
{
    use HasRealEstateReference;

    protected $table = 're_rent_invoices';

    protected $fillable = [
        'reference', 'lease_id', 'billing_period', 'due_date',
        'rent_amount', 'penalty_amount', 'total_amount',
        'status', 'paid_at', 'transaction_id', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'billing_period' => 'date',
            'due_date'       => 'date',
            'paid_at'        => 'date',
            'rent_amount'    => 'decimal:2',
            'penalty_amount' => 'decimal:2',
            'total_amount'   => 'decimal:2',
        ];
    }

    public function lease(): BelongsTo
    {
        return $this->belongsTo(Lease::class);
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(AccountTransaction::class, 'transaction_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeOverdue(Builder $query): Builder
    {
        return $query->whereIn('status', ['invoiced', 'overdue'])
            ->where('due_date', '<', Carbon::today());
    }

    public function scopePendingPenalty(Builder $query, Carbon $asOf): Builder
    {
        return $query->whereIn('status', ['invoiced', 'overdue'])
            ->where('due_date', '<', $asOf);
    }

    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }

    public function isOverdue(): bool
    {
        return in_array($this->status, ['invoiced', 'overdue'])
            && $this->due_date->lt(Carbon::today());
    }
}

<?php

namespace App\Models;

use App\Models\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SalesOrder extends Model
{
    use BelongsToTenant;

    public const STATUSES = ['draft', 'confirmed', 'fulfilled', 'cancelled'];

    /** Statuses that still need moving, or are being moved. */
    public const OPEN_TRANSPORT_STATUSES = ['pending', 'approved', 'in_transit'];

    protected $fillable = [
        'tenant_id', 'reference', 'sold_by',
        'customer_name', 'customer_email', 'customer_phone', 'customer_address', 'delivery_address',
        'currency', 'tax_rate', 'subtotal', 'tax_amount', 'total_amount',
        'status', 'order_date', 'notes', 'confirmed_at', 'fulfilled_at',
    ];

    protected function casts(): array
    {
        return [
            'tax_rate' => 'decimal:2',
            'subtotal' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'order_date' => 'date',
            'confirmed_at' => 'datetime',
            'fulfilled_at' => 'datetime',
        ];
    }

    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sold_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(SalesOrderItem::class)->orderBy('position')->orderBy('id');
    }

    public function transportRequests(): HasMany
    {
        return $this->hasMany(TransportRequest::class)->latest();
    }

    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    /**
     * Only draft orders may be changed by their owner.
     */
    public function isEditable(): bool
    {
        return $this->isDraft();
    }

    public function canBeFulfilled(): bool
    {
        return $this->status === 'confirmed';
    }

    public function hasOpenTransportRequest(): bool
    {
        return $this->transportRequests()
            ->whereIn('status', self::OPEN_TRANSPORT_STATUSES)
            ->exists();
    }

    /**
     * One line describing what has to be moved, e.g. "2 × Widget, 1 × Stand".
     */
    public function itemsSummary(): string
    {
        return $this->items
            ->map(fn (SalesOrderItem $item) => rtrim(rtrim(number_format((float) $item->quantity, 2, '.', ''), '0'), '.').' × '.$item->description)
            ->implode(', ');
    }

    /**
     * A per-company, per-year human reference such as SO-2026-00001.
     */
    public static function generateReference(): string
    {
        $prefix = (string) config('sales.order_prefix', 'SO');
        $year = now()->year;
        $sequence = static::whereYear('created_at', $year)->count() + 1;

        do {
            $reference = sprintf('%s-%d-%s', $prefix, $year, str_pad((string) $sequence, 5, '0', STR_PAD_LEFT));
            $sequence++;
        } while (static::where('reference', $reference)->exists());

        return $reference;
    }

    public function scopeStatus($query, string $status)
    {
        return $query->where('status', $status);
    }
}

<?php

namespace App\Models;

use App\Models\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TransportRequest extends Model
{
    use BelongsToTenant;

    public const STATUSES = ['pending', 'approved', 'in_transit', 'delivered', 'cancelled'];

    protected $fillable = [
        'tenant_id', 'reference', 'sales_order_id', 'requested_by',
        'pickup_address', 'delivery_address', 'pickup_contact', 'delivery_contact', 'items_summary',
        'scheduled_date', 'vehicle', 'driver_id', 'status', 'notes',
        'approved_by', 'approved_at', 'dispatched_at', 'delivered_at',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_date' => 'date',
            'approved_at' => 'datetime',
            'dispatched_at' => 'datetime',
            'delivered_at' => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(SalesOrder::class, 'sales_order_id');
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'driver_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * A request that still needs attention — not yet finished or cancelled.
     */
    public function isOpen(): bool
    {
        return in_array($this->status, ['pending', 'approved', 'in_transit'], true);
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * A per-company, per-year human reference such as TR-2026-00001.
     */
    public static function generateReference(): string
    {
        $prefix = (string) config('sales.transport_prefix', 'TR');
        $year = now()->year;
        $sequence = static::whereYear('created_at', $year)->count() + 1;

        do {
            $reference = sprintf('%s-%d-%s', $prefix, $year, str_pad((string) $sequence, 5, '0', STR_PAD_LEFT));
            $sequence++;
        } while (static::where('reference', $reference)->exists());

        return $reference;
    }

    public function scopeOpen($query)
    {
        return $query->whereIn('status', ['pending', 'approved', 'in_transit']);
    }
}

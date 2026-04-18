<?php

namespace App\Models\RealEstate;

use App\Models\Traits\HasRealEstateReference;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MaintenanceRequest extends Model
{
    use HasRealEstateReference;

    protected static function booted(): void
    {
        static::addGlobalScope('portal_tenant', function (Builder $query) {
            if (auth()->check()) {
                $reTenant = \App\Models\RealEstate\RealEstateTenant::withoutGlobalScopes()
                    ->where('user_id', auth()->id())
                    ->first();
                if ($reTenant) {
                    $query->where('re_tenant_id', $reTenant->id);
                }
            }
        });
    }

    protected $table = 're_maintenance_requests';

    protected $fillable = [
        'reference', 'property_id', 'lease_id', 're_tenant_id',
        'title', 'description', 'category', 'priority', 'status',
        'assigned_to', 'assigned_at', 'resolved_at', 'resolution_notes',
        'repair_cost', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'assigned_at' => 'datetime',
            'resolved_at' => 'datetime',
            'repair_cost' => 'decimal:2',
        ];
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function lease(): BelongsTo
    {
        return $this->belongsTo(Lease::class);
    }

    public function reTenant(): BelongsTo
    {
        return $this->belongsTo(RealEstateTenant::class, 're_tenant_id');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->where('status', 'open');
    }

    public function scopeInProgress(Builder $query): Builder
    {
        return $query->where('status', 'in_progress');
    }

    public function scopeResolved(Builder $query): Builder
    {
        return $query->where('status', 'resolved');
    }

    public function isOpen(): bool
    {
        return $this->status === 'open';
    }

    public function isResolved(): bool
    {
        return $this->status === 'resolved';
    }

    public function isClosed(): bool
    {
        return $this->status === 'closed';
    }
}

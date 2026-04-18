<?php

namespace App\Models\RealEstate;

use App\Models\Traits\HasRealEstateReference;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Property extends Model
{
    use HasRealEstateReference, SoftDeletes;

    protected $table = 're_properties';

    protected $fillable = [
        'reference', 'tenant_id', 'name', 'type', 'address', 'city',
        'country', 'units', 'market_value', 'status', 'description', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'market_value' => 'decimal:2',
            'units'        => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', function (Builder $query) {
            if (auth()->check()) {
                $query->where('tenant_id', auth()->id());
            }
        });
    }

    public function leases(): HasMany
    {
        return $this->hasMany(Lease::class);
    }

    public function maintenanceRequests(): HasMany
    {
        return $this->hasMany(MaintenanceRequest::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeAvailable(Builder $query): Builder
    {
        return $query->where('status', 'available');
    }

    public function scopeFullyLet(Builder $query): Builder
    {
        return $query->where('status', 'fully_let');
    }

    public function scopePartiallyLet(Builder $query): Builder
    {
        return $query->where('status', 'partially_let');
    }

    public function isFullyLet(): bool
    {
        return $this->leases()->where('status', 'active')->count() >= $this->units;
    }
}

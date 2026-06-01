<?php

namespace App\Models\RealEstate;

use App\Models\Traits\HasRealEstateReference;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class PropertyUnit extends Model
{
    use HasRealEstateReference, SoftDeletes;

    protected $table = 're_property_units';

    protected $fillable = [
        'reference', 'property_id', 'unit_number', 'type', 'bedrooms',
        'bathrooms', 'square_meters', 'rent_amount', 'sale_price',
        'listing_type', 'status', 'description', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'rent_amount' => 'decimal:2',
            'sale_price' => 'decimal:2',
            'square_meters' => 'decimal:2',
        ];
    }

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', function (Builder $query) {
            if (auth()->check()) {
                // Wait, need to check how the Property model does it - actually, Property has tenant_id as user_id,
                // but PropertyUnit belongs to Property, so maybe we should scope through property?
                // But let's keep it consistent with other models - let's see:
                $query->whereHas('property', function ($q) {
                    $q->where('tenant_id', auth()->id());
                });
            }
        });

        static::creating(function ($unit) {
            if (empty($unit->reference)) {
                $unit->reference = self::generateReference('UNIT');
            }
        });
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function agentAssignments(): HasMany
    {
        return $this->hasMany(AgentAssignment::class, 'unit_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeAvailable(Builder $query): Builder
    {
        return $query->where('status', 'available');
    }

    public function scopeOccupied(Builder $query): Builder
    {
        return $query->where('status', 'occupied');
    }

    public function scopeForRent(Builder $query): Builder
    {
        return $query->whereIn('listing_type', ['rent', 'both']);
    }

    public function scopeForSale(Builder $query): Builder
    {
        return $query->whereIn('listing_type', ['sale', 'both']);
    }
}

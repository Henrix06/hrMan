<?php

namespace App\Models\RealEstate;

use App\Models\Traits\HasRealEstateReference;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ConveyanceDocument extends Model
{
    use HasRealEstateReference, SoftDeletes;

    protected $table = 're_conveyance_documents';

    protected $fillable = [
        'reference', 'company_tenant_id', 'unit_id', 'property_id',
        'seller_name', 'seller_id_number', 'seller_address',
        'buyer_name', 'buyer_id_number', 'buyer_address',
        'purchase_price', 'currency', 'agreement_date', 'transfer_date',
        'deed_number', 'stand_number', 'survey_number',
        'conveyancer_name', 'conveyancer_reference',
        'transfer_duty', 'conveyancing_fee', 'registration_fee', 'other_fees',
        'notes', 'status', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'purchase_price' => 'decimal:2',
            'transfer_duty' => 'decimal:2',
            'conveyancing_fee' => 'decimal:2',
            'registration_fee' => 'decimal:2',
            'other_fees' => 'decimal:2',
            'agreement_date' => 'date',
            'transfer_date' => 'date',
        ];
    }

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', function (Builder $query) {
            if (auth()->check()) {
                $query->where('company_tenant_id', auth()->id());
            }
        });

        static::creating(function ($document) {
            if (empty($document->reference)) {
                $document->reference = self::generateReference('CONVEY');
            }
        });
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(PropertyUnit::class, 'unit_id');
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function companyTenant(): BelongsTo
    {
        return $this->belongsTo(User::class, 'company_tenant_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getTotalFeesAttribute(): float
    {
        return $this->transfer_duty + $this->conveyancing_fee + $this->registration_fee + $this->other_fees;
    }

    public function scopeDraft(Builder $query): Builder
    {
        return $query->where('status', 'draft');
    }

    public function scopeInProgress(Builder $query): Builder
    {
        return $query->where('status', 'in_progress');
    }

    public function scopeTransferred(Builder $query): Builder
    {
        return $query->where('status', 'transferred');
    }
}

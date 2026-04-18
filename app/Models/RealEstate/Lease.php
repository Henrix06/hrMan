<?php

namespace App\Models\RealEstate;

use App\Models\Account;
use App\Models\Traits\HasRealEstateReference;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Lease extends Model
{
    use HasRealEstateReference, SoftDeletes;

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

    protected $table = 're_leases';

    protected $fillable = [
        'reference', 'property_id', 're_tenant_id', 'account_id',
        'start_date', 'end_date', 'monthly_rent', 'deposit_amount',
        'deposit_paid', 'payment_due_day', 'penalty_rate', 'grace_period_days',
        'status', 'terminated_at', 'termination_reason', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'start_date'     => 'date',
            'end_date'       => 'date',
            'terminated_at'  => 'date',
            'monthly_rent'   => 'decimal:2',
            'deposit_amount' => 'decimal:2',
            'penalty_rate'   => 'decimal:2',
            'deposit_paid'   => 'boolean',
        ];
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function reTenant(): BelongsTo
    {
        return $this->belongsTo(RealEstateTenant::class, 're_tenant_id');
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function rentInvoices(): HasMany
    {
        return $this->hasMany(RentInvoice::class);
    }

    public function maintenanceRequests(): HasMany
    {
        return $this->hasMany(MaintenanceRequest::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    public function scopeDueOn(Builder $query, Carbon $date): Builder
    {
        return $query->where('payment_due_day', $date->day)
            ->where('start_date', '<=', $date)
            ->where('end_date', '>=', $date)
            ->where('status', 'active');
    }

    public function scopeExpiringSoon(Builder $query, int $days = 30): Builder
    {
        return $query->where('status', 'active')
            ->whereBetween('end_date', [Carbon::today(), Carbon::today()->addDays($days)]);
    }
}

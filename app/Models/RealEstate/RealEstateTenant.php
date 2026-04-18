<?php

namespace App\Models\RealEstate;

use App\Models\Traits\HasRealEstateReference;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class RealEstateTenant extends Model
{
    use HasRealEstateReference, SoftDeletes;

    protected $table = 're_tenants';

    protected $fillable = [
        'reference', 'company_tenant_id', 'user_id', 'name', 'email',
        'phone', 'id_number', 'type', 'address', 'status', 'notes', 'created_by',
    ];

    protected function casts(): array
    {
        return [];
    }

    protected static function booted(): void
    {
        static::addGlobalScope('company_tenant', function (Builder $query) {
            if (auth()->check()) {
                $query->where('company_tenant_id', auth()->id());
            }
        });
    }

    public function leases(): HasMany
    {
        return $this->hasMany(Lease::class, 're_tenant_id');
    }

    public function portalUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isBlacklisted(): bool
    {
        return $this->status === 'blacklisted';
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }
}

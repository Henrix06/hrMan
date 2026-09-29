<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LicenseRedemption extends Model
{
    protected $fillable = [
        'license_code_id', 'tenant_id', 'user_id',
        'plan', 'duration_days', 'subscription_ends_at',
    ];

    protected function casts(): array
    {
        return [
            'duration_days' => 'integer',
            'subscription_ends_at' => 'datetime',
        ];
    }

    public function licenseCode(): BelongsTo
    {
        return $this->belongsTo(LicenseCode::class);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function planName(): string
    {
        return config("pricing.plans.{$this->plan}.name") ?? ucfirst($this->plan);
    }
}

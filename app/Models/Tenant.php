<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tenant extends Model
{
    protected $fillable = [
        'name',
        'subdomain',
        'plan',
        'billing_cycle',
        'status',
        'trial_ends_at',
        'subscription_ends_at',
        'payment_method',
    ];

    protected $casts = [
        'trial_ends_at'         => 'datetime',
        'subscription_ends_at'  => 'datetime',
        'payment_method'        => 'array',
    ];

    // ── Relationships ──────────────────────────────────────────────────────────

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function modules(): HasMany
    {
        return $this->hasMany(TenantModule::class);
    }

    // ── Helpers ────────────────────────────────────────────────────────────────

    public function getPlanConfig(): array
    {
        return config("pricing.plans.{$this->plan}", []);
    }

    public function isOnTrial(): bool
    {
        return $this->trial_ends_at !== null && $this->trial_ends_at->isFuture();
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * The URL for this tenant's instance.
     */
    public function url(): string
    {
        $base = config('app.url');
        $parsed = parse_url($base);
        $scheme = $parsed['scheme'] ?? 'http';
        $host   = $parsed['host'] ?? 'localhost';
        $port   = isset($parsed['port']) ? ':' . $parsed['port'] : '';

        return "{$scheme}://{$this->subdomain}.{$host}{$port}";
    }
}

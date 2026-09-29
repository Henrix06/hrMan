<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LicenseCode extends Model
{
    /**
     * Characters used in generated codes. Ambiguous glyphs (0/O, 1/I/L) are omitted.
     */
    private const ALPHABET = 'ABCDEFGHJKMNPQRSTUVWXYZ23456789';

    protected $fillable = [
        'code', 'plan', 'duration_days', 'max_redemptions',
        'times_redeemed', 'expires_at', 'is_active', 'notes', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'duration_days' => 'integer',
            'max_redemptions' => 'integer',
            'times_redeemed' => 'integer',
            'expires_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    public function redemptions(): HasMany
    {
        return $this->hasMany(LicenseRedemption::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function planConfig(): array
    {
        return config("pricing.plans.{$this->plan}", []);
    }

    public function planName(): string
    {
        return $this->planConfig()['name'] ?? ucfirst($this->plan);
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    /**
     * Redemptions left, or null when the code is unlimited.
     */
    public function remainingRedemptions(): ?int
    {
        if ($this->max_redemptions === null) {
            return null;
        }

        return max(0, $this->max_redemptions - $this->times_redeemed);
    }

    public function hasRemainingRedemptions(): bool
    {
        return $this->remainingRedemptions() !== 0;
    }

    public function isRedeemable(): bool
    {
        return $this->is_active
            && ! $this->isExpired()
            && $this->hasRemainingRedemptions();
    }

    public function redeemedByTenant(int $tenantId): bool
    {
        return $this->redemptions()->where('tenant_id', $tenantId)->exists();
    }

    /**
     * Normalise user input so " umsizi demo pro 2026 " and the stored code match.
     */
    public static function normalize(string $code): string
    {
        return (string) preg_replace('/\s+/', '', strtoupper(trim($code)));
    }

    /**
     * Generate a unique, human-friendly code such as UMSIZI-7K3M-9QP2-XY4T.
     */
    public static function generate(string $prefix = 'UMSIZI', int $groups = 3, int $groupLength = 4): string
    {
        $alphabet = self::ALPHABET;
        $max = strlen($alphabet) - 1;

        do {
            $segments = [];

            for ($group = 0; $group < $groups; $group++) {
                $segment = '';

                for ($i = 0; $i < $groupLength; $i++) {
                    $segment .= $alphabet[random_int(0, $max)];
                }

                $segments[] = $segment;
            }

            $code = strtoupper($prefix).'-'.implode('-', $segments);
        } while (self::where('code', $code)->exists());

        return $code;
    }
}

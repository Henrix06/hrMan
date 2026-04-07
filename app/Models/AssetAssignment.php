<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Hash;

class AssetAssignment extends Model
{
    protected $fillable = [
        'asset_id', 'user_id', 'assigned_by', 'returned_to',
        'assigned_date', 'expected_return_date', 'actual_return_date',
        'status', 'notes', 'return_notes', 'condition_on_return',
        'integrity_hash',
    ];

    protected function casts(): array
    {
        return [
            'assigned_date' => 'date',
            'expected_return_date' => 'date',
            'actual_return_date' => 'date',
        ];
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    public function returnedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'returned_to');
    }

    public function isOverdue(): bool
    {
        return $this->status === 'active'
            && $this->expected_return_date
            && $this->expected_return_date->isPast();
    }

    /**
     * Compute the integrity hash for this assignment record.
     * Covers the immutable core fields so any tampering is detectable.
     */
    public function computeIntegrityHash(): string
    {
        $payload = implode('|', [
            $this->asset_id,
            $this->user_id,
            $this->assigned_by,
            $this->assigned_date?->toDateString(),
            $this->created_at?->toIso8601String() ?? now()->toIso8601String(),
        ]);

        return hash('sha256', $payload);
    }

    /**
     * Verify the stored hash matches the computed one.
     */
    public function verifyIntegrity(): bool
    {
        if (!$this->integrity_hash) {
            return false;
        }

        return hash_equals($this->integrity_hash, $this->computeIntegrityHash());
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeOverdue($query)
    {
        return $query->where('status', 'active')
            ->whereNotNull('expected_return_date')
            ->where('expected_return_date', '<', now()->toDateString());
    }
}

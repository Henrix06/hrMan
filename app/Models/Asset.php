<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Asset extends Model
{
    protected $fillable = [
        'asset_tag', 'name', 'category', 'brand', 'model',
        'serial_number', 'purchase_date', 'purchase_cost',
        'condition', 'status', 'notes', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'purchase_date' => 'date',
            'purchase_cost' => 'decimal:2',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(AssetAssignment::class);
    }

    public function activeAssignment(): HasOne
    {
        return $this->hasOne(AssetAssignment::class)->where('status', 'active');
    }

    public function isAvailable(): bool
    {
        return $this->status === 'available';
    }

    public function isAssigned(): bool
    {
        return $this->status === 'assigned';
    }

    /**
     * Generate a unique asset tag like AST-2026-00042
     */
    public static function generateAssetTag(): string
    {
        $year = date('Y');
        $last = self::whereYear('created_at', $year)->max('id') ?? 0;
        return 'AST-' . $year . '-' . str_pad($last + 1, 5, '0', STR_PAD_LEFT);
    }

    public function scopeAvailable($query)
    {
        return $query->where('status', 'available');
    }

    public function scopeAssigned($query)
    {
        return $query->where('status', 'assigned');
    }
}

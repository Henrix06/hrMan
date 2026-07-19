<?php

namespace App\Models;

use App\Models\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeaveBalance extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'user_id', 'leave_type_id', 'days_available', 'days_taken', 'days_pending', 'year', 'tenant_id',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'days_available' => 'integer',
            'days_taken' => 'integer',
            'days_pending' => 'integer',
            'year' => 'integer',
        ];
    }

    /**
     * Get the user that owns the leave balance.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the leave type for the balance.
     */
    public function leaveType(): BelongsTo
    {
        return $this->belongsTo(LeaveType::class);
    }

    /**
     * Calculate remaining days.
     */
    public function getRemainingDaysAttribute(): int
    {
        return $this->days_available - $this->days_taken - $this->days_pending;
    }
}

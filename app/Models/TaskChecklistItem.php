<?php

namespace App\Models;

use App\Models\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaskChecklistItem extends Model
{
    use BelongsToTenant;

    protected $fillable = ['task_id', 'label', 'is_completed', 'position', 'completed_at', 'tenant_id'];

    protected function casts(): array
    {
        return [
            'is_completed' => 'boolean',
            'completed_at' => 'datetime',
        ];
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    public function complete(): void
    {
        $this->forceFill([
            'is_completed' => true,
            'completed_at' => now(),
        ])->save();
    }

    public function reopen(): void
    {
        $this->forceFill([
            'is_completed' => false,
            'completed_at' => null,
        ])->save();
    }
}

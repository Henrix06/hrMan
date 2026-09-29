<?php

namespace App\Models;

use App\Models\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

class Task extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'title', 'description', 'created_by', 'assigned_to',
        'department_id', 'status', 'priority', 'visibility', 'labels', 'due_date',
        'completed_at', 'tenant_id',
    ];

    protected function casts(): array
    {
        return [
            'due_date' => 'date',
            'completed_at' => 'datetime',
            'labels' => 'array',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(TaskComment::class);
    }

    public function watchers(): HasMany
    {
        return $this->hasMany(TaskWatcher::class);
    }

    public function checklistItems(): HasMany
    {
        return $this->hasMany(TaskChecklistItem::class)->orderBy('position')->orderBy('id');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(TaskAttachment::class)->latest();
    }

    public function activities(): HasMany
    {
        return $this->hasMany(TaskActivity::class)->latest();
    }

    /**
     * Ids of everyone following this task.
     *
     * @return Collection<int, int>
     */
    public function watcherIds(): Collection
    {
        return $this->watchers->pluck('user_id');
    }

    public function isWatchedBy(?int $userId): bool
    {
        return $userId !== null && $this->watcherIds()->contains($userId);
    }

    public function hasLabel(string $label): bool
    {
        return in_array($label, $this->labels ?? [], true);
    }

    /**
     * Checklist completion summary for progress bars.
     *
     * @return array{done: int, total: int, percent: int}
     */
    public function checklistProgress(): array
    {
        $items = $this->relationLoaded('checklistItems')
            ? $this->checklistItems
            : $this->checklistItems()->get();

        $total = $items->count();
        $done = $items->where('is_completed', true)->count();

        return [
            'done' => $done,
            'total' => $total,
            'percent' => $total === 0 ? 0 : (int) round($done / $total * 100),
        ];
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeInProgress($query)
    {
        return $query->where('status', 'in_progress');
    }

    public function isOverdue(): bool
    {
        return $this->due_date && $this->due_date->isPast() && $this->status !== 'completed';
    }
}

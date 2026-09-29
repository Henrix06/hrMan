<?php

namespace App\Models;

use App\Models\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaskActivity extends Model
{
    use BelongsToTenant;

    protected $fillable = ['task_id', 'user_id', 'type', 'description', 'meta', 'tenant_id'];

    protected function casts(): array
    {
        return ['meta' => 'array'];
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Append an entry to a task's timeline.
     */
    public static function record(Task $task, string $type, string $description, array $meta = []): self
    {
        return self::create([
            'task_id' => $task->id,
            'user_id' => auth()->id(),
            'type' => $type,
            'description' => $description,
            'meta' => $meta ?: null,
            'tenant_id' => $task->tenant_id,
        ]);
    }

    /**
     * Material icon key used when rendering the timeline.
     */
    public function icon(): string
    {
        $icons = [
            'created' => 'add_task',
            'updated' => 'edit',
            'status_changed' => 'swap_horiz',
            'priority_changed' => 'flag',
            'assigned' => 'person_add',
            'due_date_changed' => 'event',
            'labels_updated' => 'label',
            'watcher_added' => 'visibility',
            'watcher_removed' => 'visibility_off',
            'checklist_item_added' => 'checklist',
            'checklist_item_completed' => 'check_box',
            'checklist_item_reopened' => 'check_box_outline_blank',
            'checklist_item_removed' => 'delete',
            'attachment_added' => 'attach_file',
            'attachment_removed' => 'remove_circle',
            'mentioned' => 'alternate_email',
        ];

        return $icons[$this->type] ?? 'history';
    }
}

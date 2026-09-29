<?php

namespace App\Services;

use App\Exceptions\Task\TaskCollaborationException;
use App\Models\HrNotification;
use App\Models\Task;
use App\Models\TaskActivity;
use App\Models\TaskAttachment;
use App\Models\TaskChecklistItem;
use App\Models\TaskComment;
use App\Models\TaskWatcher;
use App\Models\User;
use App\Support\Tasks\MentionParser;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

/**
 * Everything that makes a task collaborative: who is following it, how it is
 * labelled and broken down, the files attached to it, who gets told what, and
 * the timeline of everything that happened.
 */
class TaskCollaborationService
{
    public function __construct(private MentionParser $mentions) {}

    /* ---------------------------------------------------------------------
     |  Watchers
     * ------------------------------------------------------------------- */

    public function isWatching(Task $task, ?User $user = null): bool
    {
        $user ??= auth()->user();

        return $user !== null
            && $task->watchers()->where('user_id', $user->id)->exists();
    }

    /**
     * Follow or unfollow a task; returns true when the user now watches it.
     */
    public function toggleWatch(Task $task, User $user): bool
    {
        if ($this->isWatching($task, $user)) {
            $this->unwatch($task, $user);

            return false;
        }

        $this->watch($task, $user);

        return true;
    }

    public function watch(Task $task, User $user): TaskWatcher
    {
        $watcher = TaskWatcher::firstOrCreate(
            ['task_id' => $task->id, 'user_id' => $user->id],
            ['tenant_id' => $task->tenant_id],
        );

        if ($watcher->wasRecentlyCreated) {
            TaskActivity::record($task, 'watcher_added', "{$user->name} is now watching this task", [
                'user_id' => $user->id,
            ]);
        }

        return $watcher;
    }

    public function unwatch(Task $task, User $user): void
    {
        $removed = $task->watchers()->where('user_id', $user->id)->delete();

        if ($removed) {
            TaskActivity::record($task, 'watcher_removed', "{$user->name} stopped watching this task", [
                'user_id' => $user->id,
            ]);
        }
    }

    /**
     * @return Collection<int, int>
     */
    public function watcherIds(Task $task): Collection
    {
        return $task->watchers()->pluck('user_id');
    }

    /**
     * Ping everyone following a task.
     *
     * @param  array<int, int>  $except
     */
    public function notifyWatchers(Task $task, string $type, string $title, string $message, array $except = []): void
    {
        foreach ($this->watcherIds($task)->unique() as $userId) {
            if (in_array((int) $userId, array_map('intval', $except), true)) {
                continue;
            }

            HrNotification::notify((int) $userId, $type, $title, $message, route('tasks.index'));
        }
    }

    /* ---------------------------------------------------------------------
     |  Labels
     * ------------------------------------------------------------------- */

    public function normaliseLabel(string $label): string
    {
        $label = preg_replace('/\s+/', ' ', trim($label)) ?? '';
        $max = (int) config('tasks.max_label_length', 30);

        return mb_substr($label, 0, $max);
    }

    public function addLabel(Task $task, string $label, User $actor): void
    {
        $label = $this->normaliseLabel($label);

        if ($label === '') {
            throw TaskCollaborationException::emptyLabel();
        }

        $labels = $task->labels ?? [];

        foreach ($labels as $existing) {
            if (strcasecmp($existing, $label) === 0) {
                throw TaskCollaborationException::duplicateLabel($label);
            }
        }

        $max = (int) config('tasks.max_labels', 10);

        if (count($labels) >= $max) {
            throw TaskCollaborationException::labelLimitReached($max);
        }

        $labels[] = $label;
        $task->labels = $labels;
        $task->save();

        TaskActivity::record($task, 'labels_updated', "{$actor->name} added the label \"{$label}\"", [
            'label' => $label,
        ]);
    }

    public function removeLabel(Task $task, string $label, User $actor): void
    {
        $labels = $task->labels ?? [];
        $remaining = array_values(array_filter($labels, fn ($existing) => strcasecmp($existing, $label) !== 0));

        if (count($remaining) === count($labels)) {
            return;
        }

        $task->labels = $remaining;
        $task->save();

        TaskActivity::record($task, 'labels_updated', "{$actor->name} removed the label \"{$label}\"", [
            'label' => $label,
        ]);
    }

    /* ---------------------------------------------------------------------
     |  Checklist
     * ------------------------------------------------------------------- */

    public function addChecklistItem(Task $task, string $label, User $actor): TaskChecklistItem
    {
        $label = trim($label);

        if ($label === '') {
            throw TaskCollaborationException::emptyChecklistItem();
        }

        $item = TaskChecklistItem::create([
            'task_id' => $task->id,
            'label' => mb_substr($label, 0, 200),
            'position' => (int) $task->checklistItems()->max('position') + 1,
            'tenant_id' => $task->tenant_id,
        ]);

        TaskActivity::record($task, 'checklist_item_added', "{$actor->name} added \"{$item->label}\" to the checklist");

        return $item;
    }

    /**
     * Tick or untick an item; returns true when it ends up completed.
     */
    public function toggleChecklistItem(TaskChecklistItem $item, User $actor): bool
    {
        $task = $item->task;

        if ($item->is_completed) {
            $item->reopen();
            TaskActivity::record($task, 'checklist_item_reopened', "{$actor->name} reopened \"{$item->label}\"");

            return false;
        }

        $item->complete();
        TaskActivity::record($task, 'checklist_item_completed', "{$actor->name} completed \"{$item->label}\"");

        return true;
    }

    public function removeChecklistItem(TaskChecklistItem $item, User $actor): void
    {
        $task = $item->task;
        $label = $item->label;

        $item->delete();

        TaskActivity::record($task, 'checklist_item_removed', "{$actor->name} removed \"{$label}\" from the checklist");
    }

    /* ---------------------------------------------------------------------
     |  Attachments
     * ------------------------------------------------------------------- */

    public function attachmentDisk(): string
    {
        return (string) config('tasks.attachment_disk', 'local');
    }

    public function attach(Task $task, UploadedFile $file, User $actor): TaskAttachment
    {
        $max = (int) config('tasks.max_attachments', 10);

        if ($task->attachments()->count() >= $max) {
            throw TaskCollaborationException::attachmentLimitReached($max);
        }

        $path = $file->store("task-attachments/{$task->tenant_id}/{$task->id}", $this->attachmentDisk());

        $attachment = TaskAttachment::create([
            'task_id' => $task->id,
            'user_id' => $actor->id,
            'original_name' => $file->getClientOriginalName(),
            'path' => $path,
            'mime_type' => $file->getClientMimeType(),
            'size' => $file->getSize() ?: 0,
            'tenant_id' => $task->tenant_id,
        ]);

        TaskActivity::record($task, 'attachment_added', "{$actor->name} attached {$attachment->original_name}");

        return $attachment;
    }

    public function removeAttachment(TaskAttachment $attachment, User $actor): void
    {
        $task = $attachment->task;
        $name = $attachment->original_name;
        $path = $attachment->path;

        $attachment->delete();

        Storage::disk($this->attachmentDisk())->delete($path);

        TaskActivity::record($task, 'attachment_removed', "{$actor->name} removed the attachment {$name}");
    }

    /* ---------------------------------------------------------------------
     |  Comments and @mentions
     * ------------------------------------------------------------------- */

    /**
     * The tenant users mentioned in a body of text.
     *
     * @return Collection<int, User>
     */
    public function mentionedUsers(Task $task, string $body): Collection
    {
        if (! str_contains($body, '@')) {
            return collect();
        }

        $users = User::where('tenant_id', $task->tenant_id)->get(['id', 'name', 'email']);

        return $this->mentions->resolve($body, $users);
    }

    public function addComment(Task $task, string $body, User $author): TaskComment
    {
        $comment = $task->comments()->create([
            'user_id' => $author->id,
            'body' => $body,
        ]);

        $this->notifyComment($task, $body, $author);

        return $comment;
    }

    /**
     * Notify everyone involved in a task about a new comment. Mentioned users
     * get a distinct notification; nobody is told twice.
     *
     * @return Collection<int, User> the mentioned users
     */
    public function notifyComment(Task $task, string $body, User $author): Collection
    {
        $mentioned = $this->mentionedUsers($task, $body);

        $recipients = collect([$task->created_by, $task->assigned_to])
            ->merge($this->watcherIds($task))
            ->merge($mentioned->pluck('id'))
            ->filter(fn ($id) => $id !== null && (int) $id !== $author->id)
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        foreach ($recipients as $userId) {
            $isMentioned = $mentioned->contains('id', $userId);

            HrNotification::notify(
                $userId,
                $isMentioned ? 'task_mention' : 'task_comment',
                $isMentioned ? 'You were mentioned' : 'New Comment on Task',
                $isMentioned
                    ? "{$author->name} mentioned you on: {$task->title}"
                    : "{$author->name} commented on: {$task->title}",
                route('tasks.index'),
            );
        }

        foreach ($mentioned as $user) {
            if ((int) $user->id === $author->id) {
                continue;
            }

            TaskActivity::record($task, 'mentioned', "{$author->name} mentioned {$user->name}", [
                'user_id' => $user->id,
            ]);
        }

        return $mentioned;
    }

    /* ---------------------------------------------------------------------
     |  Timeline
     * ------------------------------------------------------------------- */

    public function recordCreated(Task $task, User $actor): void
    {
        TaskActivity::record($task, 'created', "{$actor->name} created this task");
    }

    public function recordStatusChange(Task $task, string $from, string $to, User $actor): void
    {
        $label = fn (string $status) => str_replace('_', ' ', $status);

        TaskActivity::record(
            $task,
            'status_changed',
            "{$actor->name} changed the status from {$label($from)} to {$label($to)}",
            ['from' => $from, 'to' => $to],
        );
    }

    public function recordAssignment(Task $task, ?int $from, ?int $to, User $actor): void
    {
        if ($from === $to) {
            return;
        }

        $name = $to === null ? null : User::whereKey($to)->value('name');
        $description = $to === null
            ? "{$actor->name} unassigned this task"
            : "{$actor->name} assigned this task to {$name}";

        TaskActivity::record($task, 'assigned', $description, ['from' => $from, 'to' => $to]);
    }

    /**
     * A catch-all entry naming the fields that changed.
     *
     * @param  array<int, string>  $fields  human-readable field names
     */
    public function recordChanges(Task $task, array $fields, User $actor): void
    {
        if ($fields === []) {
            return;
        }

        TaskActivity::record($task, 'updated', "{$actor->name} updated ".implode(', ', $fields), [
            'fields' => $fields,
        ]);
    }
}

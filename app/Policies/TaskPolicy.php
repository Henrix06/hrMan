<?php

namespace App\Policies;

use App\Models\Task;
use App\Models\User;

class TaskPolicy
{
    /**
     * Anyone authenticated can create tasks.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Only the task creator (or HR/Admin) can edit.
     * Managers viewing a subordinate's private task cannot edit it.
     */
    public function update(User $user, Task $task): bool
    {
        if ($user->isHRAdmin()) return true;
        return $task->created_by === $user->id;
    }

    /**
     * Delete restricted to HR/Admin.
     */
    public function delete(User $user, Task $task): bool
    {
        return $user->isHRAdmin();
    }

    /**
     * Can the user see this task at all?
     * - HR/Admin: always
     * - Creator: always
     * - Assignee: always
     * - Manager: can see subordinate tasks (private = read-only, team = read-only)
     * - Employee: only own created/assigned tasks
     */
    public function view(User $user, Task $task): bool
    {
        if ($user->isHRAdmin()) return true;
        if ($task->created_by === $user->id) return true;
        if ($task->assigned_to === $user->id) return true;

        // Managers can see their subordinates' tasks
        if ($user->isManager()) {
            $subordinateIds = $user->subordinates()->pluck('id');
            return $subordinateIds->contains($task->created_by)
                || $subordinateIds->contains($task->assigned_to);
        }

        return false;
    }
}

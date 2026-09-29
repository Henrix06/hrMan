<?php

namespace App\Exceptions\Task;

use RuntimeException;

class TaskCollaborationException extends RuntimeException
{
    public static function duplicateLabel(string $label): self
    {
        return new self("\"{$label}\" is already on this task.");
    }

    public static function labelLimitReached(int $max): self
    {
        return new self("A task can carry at most {$max} labels.");
    }

    public static function emptyLabel(): self
    {
        return new self('Enter a label to add it to this task.');
    }

    public static function emptyChecklistItem(): self
    {
        return new self('Enter a description for the checklist item.');
    }

    public static function attachmentLimitReached(int $max): self
    {
        return new self("A task can hold at most {$max} attachments.");
    }

    public static function alreadyWatching(): self
    {
        return new self('You are already watching this task.');
    }

    public static function notWatching(): self
    {
        return new self('You are not watching this task.');
    }

    public static function attachmentNotFound(): self
    {
        return new self('That attachment could not be found.');
    }
}

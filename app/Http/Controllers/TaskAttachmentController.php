<?php

namespace App\Http\Controllers;

use App\Models\TaskAttachment;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TaskAttachmentController extends Controller
{
    /**
     * Download an attachment, but only for people who can see its task.
     */
    public function __invoke(TaskAttachment $attachment): StreamedResponse
    {
        $task = $attachment->task;

        abort_if($task === null, 404);

        Gate::authorize('view', $task);

        $disk = (string) config('tasks.attachment_disk', 'local');

        abort_unless(Storage::disk($disk)->exists($attachment->path), 404);

        return Storage::disk($disk)->download($attachment->path, $attachment->original_name);
    }
}

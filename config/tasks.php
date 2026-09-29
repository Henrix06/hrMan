<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Task Collaboration
    |--------------------------------------------------------------------------
    | Guard rails for the collaborative parts of a task: labels, checklists and
    | file attachments.
    */

    'max_labels' => env('TASK_MAX_LABELS', 10),

    'max_label_length' => env('TASK_MAX_LABEL_LENGTH', 30),

    'max_attachments' => env('TASK_MAX_ATTACHMENTS', 10),

    'max_attachment_kb' => env('TASK_MAX_ATTACHMENT_KB', 10240),

    'allowed_attachment_extensions' => [
        'pdf', 'doc', 'docx', 'xls', 'xlsx', 'csv', 'txt', 'ppt', 'pptx',
        'png', 'jpg', 'jpeg', 'gif', 'webp', 'zip',
    ],

    'attachment_disk' => env('TASK_ATTACHMENT_DISK', 'local'),

];

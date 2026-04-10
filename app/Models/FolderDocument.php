<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FolderDocument extends Model
{
    protected $fillable = ['folder_id', 'created_by', 'title', 'content', 'type', 'meta'];

    protected function casts(): array
    {
        return ['meta' => 'array'];
    }

    public function folder(): BelongsTo
    {
        return $this->belongsTo(Folder::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}

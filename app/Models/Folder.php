<?php

namespace App\Models;

use App\Models\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Folder extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'owner_id', 'parent_id', 'name', 'description',
        'is_encrypted', 'encryption_key', 'is_public', 'tenant_id',
    ];

    protected function casts(): array
    {
        return [
            'is_encrypted' => 'boolean',
            'is_public'    => 'boolean',
        ];
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Folder::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Folder::class, 'parent_id');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(FolderDocument::class);
    }

    public function shares(): HasMany
    {
        return $this->hasMany(FolderShare::class);
    }

    public function sharedUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'folder_shares')
            ->withPivot('permission')
            ->withTimestamps();
    }

    /**
     * Can the given user access this folder?
     */
    public function isAccessibleBy(int $userId): bool
    {
        if ($this->owner_id === $userId) return true;
        if ($this->is_public) return true;
        return $this->shares()->where('user_id', $userId)->exists();
    }
}

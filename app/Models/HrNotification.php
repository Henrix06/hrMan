<?php

namespace App\Models;

use App\Models\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HrNotification extends Model
{
    use BelongsToTenant;

    protected $table = 'hr_notifications';

    protected $fillable = ['user_id', 'type', 'title', 'message', 'link', 'read_at', 'tenant_id'];

    protected function casts(): array
    {
        return ['read_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isRead(): bool
    {
        return $this->read_at !== null;
    }

    public static function notify(int $userId, string $type, string $title, string $message, ?string $link = null): self
    {
        $notif = self::create([
            'user_id' => $userId,
            'type'    => $type,
            'title'   => $title,
            'message' => $message,
            'link'    => $link,
        ]);

        // Bust the cached unread count for this user
        \Illuminate\Support\Facades\Cache::forget("unread_notif_{$userId}");

        return $notif;
    }
}

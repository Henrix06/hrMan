<?php

namespace App\Models;

use App\Models\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ChatConversation extends Model
{
    use BelongsToTenant;

    protected $fillable = ['user_one_id', 'user_two_id', 'last_message_at', 'tenant_id'];

    protected function casts(): array
    {
        return ['last_message_at' => 'datetime'];
    }

    public function userOne(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_one_id');
    }

    public function userTwo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_two_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(ChatMessage::class, 'conversation_id');
    }

    public function latestMessage(): HasOne
    {
        return $this->hasOne(ChatMessage::class, 'conversation_id')->latestOfMany();
    }

    /**
     * Get or create a conversation between two users (always store lower id first).
     */
    public static function between(int $userA, int $userB): self
    {
        [$one, $two] = $userA < $userB ? [$userA, $userB] : [$userB, $userA];

        return self::firstOrCreate(
            ['user_one_id' => $one, 'user_two_id' => $two]
        );
    }

    /**
     * Get the other participant relative to the given user.
     */
    public function otherUser(int $userId): User
    {
        return $this->user_one_id === $userId ? $this->userTwo : $this->userOne;
    }

    public function unreadCountFor(int $userId): int
    {
        return $this->messages()
            ->where('sender_id', '!=', $userId)
            ->whereNull('read_at')
            ->count();
    }
}

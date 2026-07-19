<?php

namespace App\Models;

use App\Models\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChatMessage extends Model
{
    use BelongsToTenant;

    protected $fillable = ['conversation_id', 'sender_id', 'body', 'read_at', 'tenant_id'];

    protected function casts(): array
    {
        return ['read_at' => 'datetime'];
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(ChatConversation::class, 'conversation_id');
    }
}

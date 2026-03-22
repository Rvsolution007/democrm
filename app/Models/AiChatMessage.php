<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiChatMessage extends Model
{
    protected $fillable = [
        'session_id',
        'role',
        'message',
        'message_type',
        'image_url',
        'reply_context',
    ];

    protected $casts = [
        'reply_context' => 'array',
    ];

    // Relationships
    public function session(): BelongsTo
    {
        return $this->belongsTo(AiChatSession::class, 'session_id');
    }

    // Helpers
    public function isFromUser(): bool
    {
        return $this->role === 'user';
    }

    public function isFromBot(): bool
    {
        return $this->role === 'bot';
    }

    public function hasImage(): bool
    {
        return !empty($this->image_url);
    }

    public function hasReplyContext(): bool
    {
        return !empty($this->reply_context);
    }
}

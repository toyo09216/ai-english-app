<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Message extends Model
{
    protected $fillable = [
        'thread_id',
        'message_en',
        'message_ja',
        'sender',
        'audio_file_path',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'sender' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Sender type constants
     */
    const SENDER_USER = 1;
    const SENDER_AI = 2;

    /**
     * Get the thread that owns the message.
     */
    public function thread(): BelongsTo
    {
        return $this->belongsTo(Thread::class);
    }

    /**
     * Scope a query to only include user messages.
     */
    public function scopeUser($query)
    {
        return $query->where('sender', self::SENDER_USER);
    }

    /**
     * Scope a query to only include AI messages.
     */
    public function scopeAi($query)
    {
        return $query->where('sender', self::SENDER_AI);
    }
}

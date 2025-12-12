<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Conversation extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'last_message',
    ];

    protected $casts = [
        'last_message' => 'array',
    ];

    /**
     * Get all participants in this conversation.
     */
    public function participants(): HasMany
    {
        return $this->hasMany(ConversationParticipant::class);
    }

    /**
     * Get all messages in this conversation.
     */
    public function messages(): HasMany
    {
        return $this->hasMany(Message::class)->orderBy('sent_at');
    }

    /**
     * Get all message reads through messages.
     */
    public function reads(): HasManyThrough
    {
        return $this->hasManyThrough(MessageRead::class, Message::class);
    }

    /**
     * Get the rescue request associated with this conversation.
     */
    public function rescueRequest(): HasOne
    {
        return $this->hasOne(RescueRequest::class);
    }
}

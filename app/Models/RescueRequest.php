<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RescueRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'rescue_code',
        'assigned_rescuer',
        'user_id',
        'status',
        'building_id',
        'floor_id',
        'room_id',
        'conversation_id',
        'description',
        'mobility_status',
        'injuries',
        'urgency_level',
        'additional_info',
        'firstName',
        'lastName'
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    /**
     * Get the building for this rescue request.
     */
    public function building(): BelongsTo
    {
        return $this->belongsTo(Building::class);
    }

    /**
     * Get the floor for this rescue request.
     */
    public function floor(): BelongsTo
    {
        return $this->belongsTo(Floor::class);
    }

    /**
     * Get the room for this rescue request.
     */
    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    /**
     * Get the user who requested rescue.
     */
    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the assigned rescuer.
     */
    public function rescuer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_rescuer');
    }

    /**
     * Get the conversation for this rescue request.
     */
    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    /**
     * Get the full location string.
     *
     * @return string
     */
    public function getFullLocation(): string
    {
        return trim(sprintf(
            '%s - %s - %s',
            $this->building?->name ?? '',
            $this->floor?->floor_name ?? '',
            $this->room?->room_name ?? ''
        ));
    }
}

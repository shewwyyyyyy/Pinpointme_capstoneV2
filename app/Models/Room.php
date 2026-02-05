<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Room extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'room_name',
        'floor_id',
        'file',
        'qr_data',
        'qr_updated_at',
        'qr_version'
    ];

    protected $casts = [
        'qr_updated_at' => 'datetime',
    ];

    /**
     * Get the floor that owns this room.
     */
    public function floor(): BelongsTo
    {
        return $this->belongsTo(Floor::class);
    }

    /**
     * Get the room's full location identifier.
     *
     * @return string
     */
    public function getFullLocation(): string
    {
        return trim(sprintf(
            '%s - %s - %s',
            $this->floor?->building?->name ?? '',
            $this->floor?->floor_name ?? '',
            $this->room_name
        ));
    }
}

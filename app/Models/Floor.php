<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Floor extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'floor_name',
        'building_id',
        'floor_plan_url'
    ];

    /**
     * Get the building that owns this floor.
     */
    public function building(): BelongsTo
    {
        return $this->belongsTo(Building::class);
    }

    /**
     * Get all rooms belonging to this floor.
     */
    public function rooms(): HasMany
    {
        return $this->hasMany(Room::class);
    }

    /**
     * Get the floor's full identifier (building - floor).
     *
     * @return string
     */
    public function getFullIdentifier(): string
    {
        return trim(sprintf(
            '%s - %s',
            $this->building?->name ?? '',
            $this->floor_name
        ));
    }
}

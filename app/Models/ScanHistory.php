<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScanHistory extends Model
{
    protected $fillable = [
        'profile_id',
        'scanned_at',
        'property_id',
        'meal_schedule', // e.g., breakfast, lunch, dinner
        'start_date',
        'end_date',
        'meal_count',
    ];

    /**
     * Get the profile that owns the scan history.
     *
     * @return BelongsTo
     */
    public function profile(): BelongsTo
    {
        return $this->belongsTo(Profile::class);
    }

    /**
     * Get the property associated with the scan history.
     *
     * @return BelongsTo
     */
    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }
}

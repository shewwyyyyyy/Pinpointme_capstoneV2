<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PropertyMealSchedule extends Model
{
    protected $fillable = [
        'property_id',
        'meal_schedule_id',
    ];

    /**
     * Get the property_id that owns the meal schedule.
     *
     * @return BelongsTo
     */
    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class, 'property_id');
    }

    /**
     * Get the meal schedule that the profile owns.
     *
     * @return BelongsTo
     */
    public function mealSchedule(): BelongsTo
    {
        return $this->belongsTo(MealSchedule::class, 'meal_schedule_id');
    }
}

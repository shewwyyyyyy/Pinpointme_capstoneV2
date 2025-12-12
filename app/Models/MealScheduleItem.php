<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MealScheduleItem extends Model
{
    protected $fillable = [
        'meal_schedule_id',
        'day_type',
        'time_start',
        'time_end',
        'meal_type',
    ];

    public function mealSchedule(): BelongsTo
    {
        return $this->belongsTo(MealSchedule::class, 'meal_schedule_id');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class MealSchedule extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'remarks',
        'days',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'days' => 'array',
    ];

    public function mealSchedule(): HasMany
    {
        return $this->hasMany(MealScheduleItem::class);
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(Profile::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(Profile::class, 'updated_by');
    }

    public function propertyMealSchedule(): HasOne
    {
        return $this->hasOne(PropertyMealSchedule::class);
    }
}

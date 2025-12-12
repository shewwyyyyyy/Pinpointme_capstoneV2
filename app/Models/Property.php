<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Property extends Model
{
    protected $fillable = [
        'name',
        'code',
        'description',
        'username',
        'unique_identifier',
    ];

    public function propertyMealSchedule(): HasOne
    {
        return $this->hasOne(PropertyMealSchedule::class);
    }
}

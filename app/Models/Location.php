<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Location extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'code',
        'description',
        'property_id'
    ];

    /**
     * Get all profiles belonging to this department.
     * one dept has many profile
     */
    public function profiles()
    {
        return $this->hasMany(Profile::class);
    }

    /**
     * Get the location's full identifier (code - name).
     *
     * @return string
     */
    public function getFullIdentifier(): string
    {
        return trim(sprintf(
            '%s - %s',
            $this->code,
            $this->name

        ));
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class, 'property_id');
    }
}

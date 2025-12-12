<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Building extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name'
    ];

    /**
     * Get all floors belonging to this building.
     */
    public function floors()
    {
        return $this->hasMany(Floor::class);
    }

    /**
     * Get the building's display name.
     *
     * @return string
     */
    public function getDisplayName(): string
    {
        return $this->name;
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Profile extends Model
{
    protected $fillable = [
        'user_id',
        'first_name',
        'middle_name',
        'last_name',
        'unique_identifier',
        'position',
        'department_id',
        'property_id',
        'location_id',
        'meal_entitlement',
        'start_date',
        'end_date'
    ];

    /**
     * Get the user that owns the profile.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the user that owns the profile.
     */
    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class, 'property_id');
    }

    /**
     * Get the department that the profile belongs to.
     */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'department_id');
    }

    /**
     * Get the location that the profile belongs to.
     */
    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'location_id');
    }

    /**
     * Get all scan histories for this profile.
     */
    public function scanHistories(): HasMany
    {
        return $this->hasMany(ScanHistory::class, 'profile_id');
    }

    /**
     * Get the full name of the profile, with the middle name shortened to its initials.
     *
     * Example:
     *  - middle_name: "San Jose" → "S.J."
     *
     * @return string
     */
    public function getFullName(): string
    {
        $middleInitials = '';

        if (!empty($this->middle_name)) {
            $words = preg_split('/\s+/', trim($this->middle_name));
            $initials = array_map(fn($word) => strtoupper($word[0]), $words);
            $middleInitials = implode('.', $initials) . '.';
        }

        return trim(sprintf(
            '%s %s %s',
            $this->first_name,
            $middleInitials,
            $this->last_name
        ));
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityLog extends Model
{
    protected $fillable = [
        'module',
        'description',
        'status',
        'type',
        'properties',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'properties' => 'array', // Store properties as an array
    ];

    /**
     * Get the profile that created this activity log.
     *
     * @return BelongsTo
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(Profile::class, 'created_by');
    }

    /**
     * Get the profile that last updated this activity log.
     *
     * @return BelongsTo
     */
    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(Profile::class, 'updated_by');
    }
}

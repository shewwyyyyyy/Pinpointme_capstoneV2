<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AuditTrail extends Model
{
    use HasFactory;

    protected $fillable = [
        'action',
        'initiator',
        'initiator_role',
        'account_updated',
        'details',
        'user_id',
        'entity_type',
        'entity_id',
        'description',
        'old_values',
        'new_values',
        'ip_address',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
    ];

    /**
     * Get the user that performed the action
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get formatted action description.
     *
     * @return string
     */
    public function getActionDescription(): string
    {
        if ($this->description) {
            return $this->description;
        }
        
        return trim(sprintf(
            '%s by %s',
            ucfirst($this->action),
            $this->initiator
        ));
    }
}

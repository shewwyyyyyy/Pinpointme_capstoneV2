<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'username',
        'first_name',
        'last_name',
        'email',
        'role',
        'password',
        'phone',
        'student_id',
        'faculty_id',
        'staff_id',
        'isAdmin',
        'last_login_at',
        'is_able_to_login',
        'status',
        'must_change_password',
        'force_password_change',
        'password_changed_at',
        'profile_picture',
        'tags',
        'rescuer_id',
        'require_otp',
        'otp_code',
        'otp_expires_at',
        'otp_verified',
        'emergency_contact_name',
        'emergency_contact_phone',
        'emergency_contact_relation',
        'blood_type',
        'allergies',
        'medical_conditions',
        'must_update_profile',
        'google_id',
        'google_token',
        'contact_number',
        'email_verified_at',
        'emergency_contact_relationship',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array<string>
     */
    protected $appends = [
        'phone_number',
        'contact_number',
        'id_number',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Get the profile associated with the user.
     *
     * @return HasOne<\App\Models\Profile>
     */
    public function profile(): HasOne
    {
        return $this->hasOne(Profile::class);
    }

    // Accessor for phone_number (maps to phone)
    public function getPhoneNumberAttribute()
    {
        return $this->phone;
    }

    // Mutator for phone_number (maps to phone)
    public function setPhoneNumberAttribute($value)
    {
        $this->attributes['phone'] = $value;
    }

    // Accessor for contact_number (maps to phone)
    public function getContactNumberAttribute()
    {
        return $this->phone;
    }

    // Mutator for contact_number (maps to phone)
    public function setContactNumberAttribute($value)
    {
        $this->attributes['phone'] = $value;
    }

    // Accessor for id_number (returns student_id, faculty_id, or staff_id based on role)
    public function getIdNumberAttribute()
    {
        if ($this->role === 'student' && $this->student_id) {
            return $this->student_id;
        } elseif (in_array($this->role, ['faculty', 'admin', 'rescuer']) && $this->faculty_id) {
            return $this->faculty_id;
        } elseif ($this->staff_id) {
            return $this->staff_id;
        }
        // Return any available ID
        return $this->student_id ?? $this->faculty_id ?? $this->staff_id ?? null;
    }

    // Mutator for id_number (sets appropriate ID field based on role or ID pattern)
    public function setIdNumberAttribute($value)
    {
        if (!$value) return;
        
        // If exactly 9 digits, determine role from ID
        if (preg_match('/^\d{9}$/', $value)) {
            $firstDigit = $value[0];
            
            // If starts with digit 2, it's a student
            if ($firstDigit === '2') {
                $this->attributes['student_id'] = $value;
                // Optionally update role if not already set
                if (!isset($this->attributes['role']) || empty($this->attributes['role'])) {
                    $this->attributes['role'] = 'student';
                }
            } else {
                // Otherwise (starts with 1,3,4,5,6,7,8,9), it's faculty
                $this->attributes['faculty_id'] = $value;
                // Optionally update role if not already set
                if (!isset($this->attributes['role']) || empty($this->attributes['role'])) {
                    $this->attributes['role'] = 'faculty';
                }
            }
        }
    }
}

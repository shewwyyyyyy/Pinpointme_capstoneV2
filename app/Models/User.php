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
}

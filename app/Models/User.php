<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    // use HasApiTokens, HasFactory, HasRoles , Notifiable;

    use HasApiTokens, HasFactory, HasRoles, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'phone',
        'specialization',
        'specialization_ar',
        'password',
        'is_vip',
        'avatar',
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
     * Get the avatar URL
     */
    public function getAvatarUrlAttribute(): string
    {
        if ($this->avatar) {
            // Check if image exists in new structure
            $image = Image::where('filename', $this->avatar)->first();
            if ($image) {
                return $image->url;
            }

            // Fallback to direct path in project_img/avatars
            return asset('project_img/avatars/'.$this->avatar);
        }

        return '';
    }

    /**
     * Get user's avatar image model
     */
    public function avatarImage()
    {
        return $this->morphOne(Image::class, 'imageable')->where('folder', 'avatars');
    }

    /**
     * Get all user's images
     */
    public function images()
    {
        return $this->morphMany(Image::class, 'imageable');
    }

    /**
     * Check if user has specific permission
     */

    /**
     * Check if user is an Assistant
     */
   public function isAssistant(): bool
{
    return $this->hasRole('Assistant');
}

    // Relationships


    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function appointments()
    {
        return $this->hasMany(Appointment::class, 'customer_id');
    }

    public function staffAppointments()
    {
        return $this->hasMany(Appointment::class, 'staff_id');
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class);
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class, 'customer_id');
    }

    public function services()
    {
        return $this->belongsToMany(Service::class, 'staff_services');
    }

    public function schedules()
    {
        return $this->hasMany(StaffSchedule::class);
    }

    public function activeSchedules()
    {
        return $this->hasMany(StaffSchedule::class)->where('is_active', true);
    }

    public function isSuperAdmin(): bool
    {
        return $this->hasRole('Super Admin');

    }

    public function isAdminTenant(): bool
    {
        return $this->hasRole('Admin Tenant');


    }

    public function isStaff(): bool
    {
        return $this->hasRole('Staff');

    }

    public function isCustomer(): bool
    {
        return $this->hasRole('Customer');

    }


    public function getRoleName(): ?string
    {
        return $this->getRoleNames()->first();
    }
}

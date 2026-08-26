<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, HasRoles, Notifiable;

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

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function getAvatarUrlAttribute(): string
    {
        if ($this->avatar) {
            $image = Image::where('filename', $this->avatar)->first();
            if ($image) {
                return $image->url;
            }

            return asset('project_img/avatars/'.$this->avatar);
        }

        return '';
    }

    public function avatarImage()
    {
        return $this->morphOne(Image::class, 'imageable')->where('folder', 'avatars');
    }

    public function images()
    {
        return $this->morphMany(Image::class, 'imageable');
    }

    public function isAssistant(): bool
    {
        return $this->hasRole('Assistant');
    }

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

    /**
     * Services assigned to this user when the user is acting as staff.
     * The pivot also contains staff_id for the Staff profile, so the user_id
     * key is declared explicitly to avoid Eloquent guessing the wrong key.
     */
    public function services()
    {
        return $this->belongsToMany(Service::class, 'staff_services', 'user_id', 'service_id');
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

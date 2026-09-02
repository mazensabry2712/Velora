<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, HasRoles, Notifiable;

    protected $fillable = [
        'name', 'email', 'locale', 'phone', 'specialization',
        'specialization_ar', 'password', 'is_vip', 'avatar',
    ];

    protected $hidden = ['password', 'remember_token'];

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

    public function isAssistant(): bool { return $this->hasRole('Assistant'); }
    public function tenant() { return $this->belongsTo(Tenant::class); }

    /** Optional business customer profile for an authenticated user. */
    public function customerProfile(): HasOne
    {
        return $this->hasOne(Customer::class, 'user_id');
    }

    /**
     * Legacy user-owned appointments are kept only for historical compatibility.
     * New booking flows use Customer as the business identity.
     */
    public function appointments()
    {
        return $this->hasMany(Appointment::class, 'customer_id');
    }

    public function customerAppointments(): HasManyThrough
    {
        return $this->hasManyThrough(
            Appointment::class,
            Customer::class,
            'user_id',
            'customer_id_new',
            'id',
            'id'
        );
    }

    /** Legacy staff appointments through the users identity. */
    public function staffAppointments() { return $this->hasMany(Appointment::class, 'staff_id'); }
    public function notifications() { return $this->hasMany(Notification::class); }
    public function invoices() { return $this->hasMany(Invoice::class, 'customer_id'); }

    /** Staff profile corresponding to this user account. */
    public function staffProfile()
    {
        return $this->hasOne(Staff::class, 'user_id');
    }

    /** Canonical weekly schedule through the dedicated Staff profile. */
    public function schedules(): HasManyThrough
    {
        return $this->hasManyThrough(
            StaffWorkingHours::class,
            Staff::class,
            'user_id',
            'staff_id',
            'id',
            'id'
        );
    }

    public function activeSchedules(): HasManyThrough
    {
        return $this->schedules()->where('is_working', true);
    }

    public function isSuperAdmin(): bool { return $this->hasRole('Super Admin'); }
    public function isAdminTenant(): bool { return $this->hasRole('Admin Tenant'); }
    public function isStaff(): bool { return $this->hasRole('Staff'); }
    public function isCustomer(): bool { return $this->hasRole('Customer'); }
    public function getRoleName(): ?string { return $this->getRoleNames()->first(); }
}

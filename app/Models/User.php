<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
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
        'role_id',
        'name',
        'email',
        'phone',
        'specialization',
        'specialization_ar',
        'password',
        'is_vip',
        'avatar',
        'permissions',
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
            'permissions' => 'array',
        ];
    }

    /**
     * Get the avatar URL
     */
    public function getAvatarUrlAttribute(): string
    {
        if ($this->avatar) {
            // Check if image exists in new structure
            $image = \App\Models\Image::where('filename', $this->avatar)->first();
            if ($image) {
                return $image->url;
            }
            // Fallback to direct path in project_img/avatars
            return asset('project_img/avatars/' . $this->avatar);
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
    public function hasPermission(string $permission): bool
    {
        // Admin Tenant has all permissions
        if ($this->isAdminTenant()) {
            return true;
        }

        $permissions = $this->permissions ?? [];
        return in_array($permission, $permissions);
    }

    /**
     * Check if user is an Assistant
     */
    public function isAssistant(): bool
    {
        return $this->role && $this->role->name === 'Assistant';
    }

    // Relationships
    public function role()
    {
        return $this->belongsTo(Role::class);
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

    // Helper methods
    public function isSuperAdmin(): bool
    {
        return $this->role && $this->role->name === 'Super Admin';
    }

    public function isAdminTenant(): bool
    {
        return $this->role && $this->role->name === 'Admin Tenant';
    }

    public function isStaff(): bool
    {
        return $this->role && $this->role->name === 'Staff';
    }

    public function isCustomer(): bool
    {
        return $this->role && $this->role->name === 'Customer';
    }

    public function getRoleName(): ?string
    {
        return $this->role?->name;
    }
}

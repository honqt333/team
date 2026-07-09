<?php

namespace App\Models;

use App\Models\HR\Employee;
use App\Notifications\ResetPasswordNotification;
use App\Notifications\VerifyEmailNotification;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\PermissionRegistrar;
use Spatie\Permission\Traits\HasRoles;

// @bypass-tenancy-scanner - Identity root: queried without a tenant context during auth
class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, Notifiable, SoftDeletes;

    protected $appends = [
        'photo_url',
        'can_update_photo',
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'photo_path',
        'is_active',
        'is_system_admin',
        'tenant_id',
        'current_center_id',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'two_factor_confirmed_at',
        'two_factor_type',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
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
            'is_active' => 'boolean',
            'is_system_admin' => 'boolean',
            'two_factor_confirmed_at' => 'datetime',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function centers(): BelongsToMany
    {
        return $this->belongsToMany(Center::class, 'center_user')
            ->withPivot('tenant_id')
            ->withTimestamps();
    }

    public function currentCenter(): BelongsTo
    {
        return $this->belongsTo(Center::class, 'current_center_id');
    }

    /**
     * Get the URL to the user's profile photo.
     */
    public function getPhotoUrlAttribute(): ?string
    {
        // 1. Check if user is an employee and has an HR photo
        $hrPhoto = $this->employee?->photo_path;
        if ($hrPhoto) {
            return asset('storage/'.$hrPhoto);
        }

        // 2. Check for uploaded profile photo
        if ($this->photo_path) {
            return asset('storage/'.$this->photo_path);
        }

        // 3. Fallback: UI Avatars or null
        return 'https://ui-avatars.com/api/?name='.urlencode($this->name).'&color=7F9CF5&background=EBF4FF';
    }

    /**
     * Determine if the user can update their profile photo.
     */
    public function getCanUpdatePhotoAttribute(): bool
    {
        // Employees with HR photos cannot update their own
        if ($this->employee()->exists() && $this->employee->photo_path) {
            return false;
        }

        return true;
    }

    public function employee(): HasOne
    {
        return $this->hasOne(Employee::class);
    }

    /**
     * The "booted" method of the model.
     */
    protected static function booted(): void
    {
        static::created(function (User $user) {
            // Check if this is the first user for the tenant
            $count = User::where('tenant_id', $user->tenant_id)->count();

            if ($count === 1 && ! app()->runningUnitTests()) {
                // First user gets Super Admin role
                $superAdminRole = Role::where('name', 'super_admin')
                    ->where('tenant_id', $user->tenant_id)
                    ->first();

                if ($superAdminRole) {
                    // Set permission team context
                    app(PermissionRegistrar::class)->setPermissionsTeamId($user->tenant_id);
                    $user->assignRole($superAdminRole);
                }
            }
        });
    }

    /**
     * Send the email verification notification using custom template.
     */
    public function sendEmailVerificationNotification(): void
    {
        $this->notify(new VerifyEmailNotification);
    }

    /**
     * Send the password reset notification using custom template.
     */
    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new ResetPasswordNotification($token));
    }
}

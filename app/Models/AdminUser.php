<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\SystemPermissions;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

// @bypass-tenancy-scanner - Admin-panel identity, lives outside tenant boundary
class AdminUser extends Authenticatable
{
    use HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
        'avatar',
        'role',
        'permissions',
        'is_active',
        'user_id',
        'last_login_at',
        'last_login_ip',
        // Two-factor authentication. The User model carries the same
        // three fields; without them here a mass assignment of the
        // two-factor state silently dropped it, and the admin could
        // never be challenged at login.
        'two_factor_secret',
        'two_factor_recovery_codes',
        'two_factor_confirmed_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        // The secret and the recovery codes must never reach a
        // response — not through the panel, not through a log.
        'two_factor_secret',
        'two_factor_recovery_codes',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'permissions' => 'array',
            'is_active' => 'boolean',
            'last_login_at' => 'datetime',
            'two_factor_confirmed_at' => 'datetime',
        ];
    }

    /**
     * Linked tenant user (optional).
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function activityLogs(): HasMany
    {
        return $this->hasMany(AdminActivityLog::class);
    }

    /**
     * Check if admin is active.
     */
    public function isActive(): bool
    {
        return (bool) $this->is_active;
    }

    /**
     * Check if admin is super admin.
     */
    public function isSuperAdmin(): bool
    {
        return $this->role === 'super_admin';
    }

    /**
     * Check if admin has permission.
     *
     * `super_admin` bypasses the permission list entirely — it holds
     * every ability in the registry.
     *
     * For the other roles the account's own `permissions` column is an
     * OVERRIDE: when it carries a list, that list is what the account
     * holds; when it is empty, the role's defaults apply (see
     * App\Support\SystemPermissions).
     */
    public function hasPermission(string $permission): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        return in_array($permission, $this->effectivePermissions(), true);
    }

    /**
     * The permissions this account effectively holds.
     *
     * @return array<int, string>
     */
    public function effectivePermissions(): array
    {
        $explicit = is_array($this->permissions) ? $this->permissions : null;

        return SystemPermissions::resolveFor((string) $this->role, $explicit);
    }

    /**
     * Whether the account holds at least one of the given permissions.
     *
     * @param array<int, string> $permissions
     */
    public function hasAnyPermission(array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if ($this->hasPermission($permission)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get role label.
     */
    public function getRoleLabelAttribute(): string
    {
        return match ($this->role) {
            'super_admin' => 'مدير النظام',
            'admin' => 'مسؤول',
            'support' => 'دعم فني',
            default => $this->role,
        };
    }

    /**
     * Available permissions.
     *
     * Delegates to the system permission registry, which is the single
     * source of truth for the abilities the panel knows about. The
     * admin-user form renders one checkbox per entry, so a new ability
     * added to the registry shows up there automatically.
     *
     * @return array<string, array<string, string>>
     */
    public static function getAvailablePermissions(): array
    {
        return SystemPermissions::groups();
    }

    /**
     * Log activity.
     */
    public function logActivity(string $action, ?string $modelType = null, $modelId = null, array $changes = []): void
    {
        AdminActivityLog::create([
            'admin_user_id' => $this->id,
            'action' => $action,
            'model_type' => $modelType,
            'model_id' => $modelId,
            'changes' => $changes,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    /**
     * Update login info.
     */
    public function updateLoginInfo(): void
    {
        $this->update([
            'last_login_at' => now(),
            'last_login_ip' => request()->ip(),
        ]);

        $this->logActivity('login');
    }
}

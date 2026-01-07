<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

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
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'permissions' => 'array',
            'is_active' => 'boolean',
            'last_login_at' => 'datetime',
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
     * Check if admin is super admin.
     */
    public function isSuperAdmin(): bool
    {
        return $this->role === 'super_admin';
    }

    /**
     * Check if admin has permission.
     */
    public function hasPermission(string $permission): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        return in_array($permission, $this->permissions ?? []);
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
     */
    public static function getAvailablePermissions(): array
    {
        return [
            'tenants' => [
                'tenants.view' => 'عرض المستأجرين',
                'tenants.create' => 'إنشاء مستأجر',
                'tenants.edit' => 'تعديل المستأجرين',
                'tenants.delete' => 'حذف المستأجرين',
                'tenants.impersonate' => 'الدخول كمستأجر',
            ],
            'billing' => [
                'subscriptions.view' => 'عرض الاشتراكات',
                'subscriptions.manage' => 'إدارة الاشتراكات',
                'invoices.view' => 'عرض الفواتير',
                'invoices.manage' => 'إدارة الفواتير',
            ],
            'settings' => [
                'plans.manage' => 'إدارة الباقات',
                'promocodes.manage' => 'إدارة الرموز الترويجية',
                'payment.settings' => 'إعدادات الدفع',
                'integrations.manage' => 'إدارة التكاملات',
            ],
            'credits' => [
                'sms.manage' => 'إدارة رصيد SMS',
                'whatsapp.manage' => 'إدارة رصيد WhatsApp',
            ],
        ];
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

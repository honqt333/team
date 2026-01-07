<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SystemAnnouncement extends Model
{
    protected $fillable = [
        'admin_user_id',
        'title',
        'content',
        'type',
        'target',
        'target_tenant_ids',
        'channels',
        'is_published',
        'published_at',
        'expires_at',
        'views_count',
    ];

    protected function casts(): array
    {
        return [
            'target_tenant_ids' => 'array',
            'channels' => 'array',
            'is_published' => 'boolean',
            'published_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(AdminUser::class, 'admin_user_id');
    }

    public function reads(): HasMany
    {
        return $this->hasMany(TenantAnnouncementRead::class);
    }

    public function sendLogs(): HasMany
    {
        return $this->hasMany(NotificationSendLog::class);
    }

    /**
     * Get type label.
     */
    public function getTypeLabelAttribute(): string
    {
        return match ($this->type) {
            'info' => 'معلومات',
            'warning' => 'تحذير',
            'important' => 'هام',
            'maintenance' => 'صيانة',
            default => $this->type,
        };
    }

    /**
     * Get target label.
     */
    public function getTargetLabelAttribute(): string
    {
        return match ($this->target) {
            'all' => 'الكل',
            'active' => 'النشطين',
            'trial' => 'التجريبي',
            'expired' => 'المنتهية',
            'specific' => 'محدد',
            default => $this->target,
        };
    }

    /**
     * Check if notification is active.
     */
    public function isActive(): bool
    {
        if (!$this->is_published) return false;
        if ($this->expires_at && $this->expires_at->isPast()) return false;
        return true;
    }

    /**
     * Get target tenants.
     */
    public function getTargetTenants()
    {
        $query = Tenant::query();

        return match ($this->target) {
            'all' => $query,
            'active' => $query->whereHas('subscriptions', fn($q) => $q->where('status', 'active')),
            'trial' => $query->whereHas('subscriptions', fn($q) => $q->where('status', 'trial')),
            'expired' => $query->whereHas('subscriptions', fn($q) => $q->where('status', 'expired')),
            'specific' => $query->whereIn('id', $this->target_tenant_ids ?? []),
            default => $query,
        };
    }

    /**
     * Check if tenant has read.
     */
    public function isReadBy(int $tenantId): bool
    {
        return $this->reads()->where('tenant_id', $tenantId)->exists();
    }

    /**
     * Mark as read by tenant.
     */
    public function markAsReadBy(int $tenantId): void
    {
        TenantAnnouncementRead::firstOrCreate([
            'tenant_id' => $tenantId,
            'system_announcement_id' => $this->id,
        ], [
            'read_at' => now(),
        ]);

        $this->increment('views_count');
    }

    /**
     * Scope published.
     */
    public function scopePublished($query)
    {
        return $query->where('is_published', true)
            ->where(fn($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()));
    }

    /**
     * Scope for tenant.
     */
    public function scopeForTenant($query, int $tenantId)
    {
        return $query->published()
            ->where(function ($q) use ($tenantId) {
                $q->where('target', 'all')
                  ->orWhere('target', '!=', 'specific')
                  ->orWhereJsonContains('target_tenant_ids', $tenantId);
            });
    }
}

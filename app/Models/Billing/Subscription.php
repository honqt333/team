<?php

declare(strict_types=1);

namespace App\Models\Billing;

use App\Models\BankTransferReceipt;
use App\Models\Concerns\TenantScoped;
use App\Models\Tenant;
use App\Services\Billing\SubscriptionLifecycleService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Subscription extends Model
{
    use HasFactory, TenantScoped;

    protected $fillable = [
        'tenant_id',
        'plan_id',
        'status',
        'billing_cycle',
        'starts_at',
        'ends_at',
        'trial_ends_at',
        'cancelled_at',
        'payment_method',
        'auto_renew',
        'promo_code',
        'discount_amount',
        'price',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'trial_ends_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'auto_renew' => 'boolean',
            'discount_amount' => 'decimal:2',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(SubscriptionInvoice::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(SubscriptionPayment::class);
    }

    public function receipts(): HasMany
    {
        return $this->hasMany(BankTransferReceipt::class);
    }

    /**
     * Check if subscription is active
     */
    public function isActive(): bool
    {
        return $this->status === 'active' && (! $this->ends_at || $this->ends_at->isFuture());
    }

    /**
     * Check if on trial
     */
    public function onTrial(): bool
    {
        $end = $this->trial_ends_at ?? $this->ends_at;

        return in_array($this->status, ['trial', 'trialing'], true) && $end?->isFuture();
    }

    /**
     * Check if subscription has ended but is still within the configured grace period.
     */
    public function isInGracePeriod(?int $graceDays = null): bool
    {
        if (! in_array($this->status, ['active', 'expired'], true) || ! $this->ends_at) {
            return false;
        }

        if ($this->ends_at->isFuture()) {
            return false;
        }

        $graceDays ??= app(SubscriptionLifecycleService::class)->getGracePeriodDays();

        return $graceDays > 0 && $this->ends_at->copy()->addDays($graceDays)->isFuture();
    }

    public function isExpired(): bool
    {
        if ($this->status === 'expired') {
            return true;
        }

        return $this->ends_at !== null && $this->ends_at->isPast();
    }

    /**
     * Get days remaining
     */
    public function getDaysRemainingAttribute(): int
    {
        // `diffInDays($to)` is the explicit form. `floatDiffInDays()` is
        // a Carbon ALIAS for exactly this call — the signed difference —
        // but it emits an E_USER_DEPRECATED notice on every invocation
        // and PHPStan cannot see it (it is resolved through Carbon's
        // `__call`).
        //
        // NOTE: the second argument of `diffInDays()` is the "absolute"
        // flag. `floatDiffInDays($to)` is signed, so it maps to
        // `diffInDays($to)` and NOT to `diffInDays($to, true)`.
        if ($this->onTrial()) {
            $end = $this->trial_ends_at ?? $this->ends_at;

            return $end ? max(0, (int) ceil($end->diffInDays(now()) > 0 && $end->isFuture() ? now()->diffInDays($end) : 0)) : 0;
        }

        if ($this->ends_at && $this->ends_at->isFuture()) {
            return max(0, (int) ceil(now()->diffInDays($this->ends_at)));
        }

        return 0;
    }
}

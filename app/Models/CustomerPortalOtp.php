<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// @bypass-tenancy-scanner - the customer_portal_otps table is
//   (tenant, customer)-scoped at the row level, but the model is
//   queried by CustomerPortalOtpService which always passes an
//   explicit tenant_id WHERE clause. We deliberately do not
//   apply the CenterScoped/TenantScoped global scopes here
//   because the service handles tenancy explicitly and we want
//   the queries to be obvious in the call site.
class CustomerPortalOtp extends Model
{
    use HasFactory;

    /**
     * 10 minutes from the issue timestamp.
     */
    public const TTL_MINUTES = 10;

    /**
     * 5 attempts is the upper bound before the OTP is
     * invalidated. We picked 5 because it accommodates a
     * user with a typo or two without being brute-forceable
     * (6 digits = 1M combos, 5 tries = 5/1M = 0.0005%).
     */
    public const DEFAULT_MAX_ATTEMPTS = 5;

    /**
     * The minimum gap between resends for the same
     * (tenant, phone). 60 seconds prevents the user from
     * hammering the request endpoint. The rate-limit
     * middleware gives the broader cap.
     */
    public const RESEND_COOLDOWN_SECONDS = 60;

    protected $fillable = [
        'tenant_id',
        'customer_id',
        'phone_normalized',
        'phone_hash',
        'code_hash',
        'attempts',
        'max_attempts',
        'expires_at',
        'consumed_at',
        'ip',
        'user_agent',
        'last_sent_at',
    ];

    protected function casts(): array
    {
        return [
            'attempts' => 'integer',
            'max_attempts' => 'integer',
            'expires_at' => 'datetime',
            'consumed_at' => 'datetime',
            'last_sent_at' => 'datetime',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * True if the row is still in the "verifiable" state:
     * not consumed, not expired, and under the attempt cap.
     */
    public function isUsable(): bool
    {
        if ($this->consumed_at !== null) {
            return false;
        }

        if ($this->expires_at !== null && $this->expires_at->isPast()) {
            return false;
        }

        if ($this->attempts >= $this->max_attempts) {
            return false;
        }

        return true;
    }
}

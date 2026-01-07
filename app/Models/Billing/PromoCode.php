<?php

namespace App\Models\Billing;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PromoCode extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'description',
        'discount_type',
        'discount_value',
        'max_uses',
        'times_used',
        'max_uses_per_tenant',
        'starts_at',
        'expires_at',
        'plan_id',
        'billing_cycle',
        'first_subscription_only',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'discount_value' => 'decimal:2',
            'starts_at' => 'datetime',
            'expires_at' => 'datetime',
            'first_subscription_only' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function usages(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(PromoCodeUsage::class);
    }

    /**
     * Check if promo code is valid
     */
    public function isValid(): bool
    {
        // Check if active
        if (!$this->is_active) return false;
        
        // Check max uses
        if ($this->max_uses !== null && $this->times_used >= $this->max_uses) return false;
        
        // Check start date
        if ($this->starts_at && $this->starts_at->isFuture()) return false;
        
        // Check expiry
        if ($this->expires_at && $this->expires_at->isPast()) return false;
        
        return true;
    }

    /**
     * Get discount description
     */
    public function getDiscountDescriptionAttribute(): string
    {
        return match ($this->discount_type) {
            'percentage' => "{$this->discount_value}% خصم",
            'fixed' => "{$this->discount_value} ر.س خصم",
            'trial_days' => "{$this->discount_value} أيام تجربة إضافية",
            default => '',
        };
    }

    /**
     * Calculate discount for amount
     */
    public function calculateDiscount(float $amount): float
    {
        return match ($this->discount_type) {
            'percentage' => $amount * ($this->discount_value / 100),
            'fixed' => min($this->discount_value, $amount),
            'trial_days' => 0, // Trial days don't affect price
            default => 0,
        };
    }

    /**
     * Increment usage count
     */
    public function incrementUsage(): void
    {
        $this->increment('times_used');
    }

    /**
     * Generate random code
     */
    public static function generateCode(int $length = 8): string
    {
        $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $code = '';
        for ($i = 0; $i < $length; $i++) {
            $code .= $characters[random_int(0, strlen($characters) - 1)];
        }
        return $code;
    }
}

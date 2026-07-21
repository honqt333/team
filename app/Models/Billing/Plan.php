<?php

declare(strict_types=1);

namespace App\Models\Billing;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

// @bypass-tenancy-scanner - Catalog entry: pricing tiers shared across tenants
class Plan extends Model
{
    use HasFactory;

    protected $appends = ['yearly_discount', 'features_ar', 'features_en'];

    protected $fillable = [
        'name',
        'name_ar',
        'name_en',
        'slug',
        'description',
        'description_ar',
        'description_en',
        'price_monthly',
        'price_yearly',
        'trial_days',
        'features',
        'limits',
        'is_active',
        'is_featured',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'features' => 'array',
            'limits' => 'array',
            'price_monthly' => 'decimal:2',
            'price_yearly' => 'decimal:2',
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
        ];
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    /**
     * Get yearly discount percentage
     */
    public function getYearlyDiscountAttribute(): float
    {
        if ($this->price_monthly == 0) {
            return 0;
        }
        $yearlyIfMonthly = $this->price_monthly * 12;

        return round((($yearlyIfMonthly - $this->price_yearly) / $yearlyIfMonthly) * 100);
    }

    /**
     * Get features attribute, translated according to current application locale.
     */
    public function getFeaturesAttribute($value): array
    {
        if (is_array($value)) {
            $features = $value;
        } elseif (is_string($value)) {
            $features = json_decode($value, true) ?? [];
        } else {
            $features = [];
        }

        if (isset($features['ar']) || isset($features['en'])) {
            $locale = app()->getLocale();

            return $features[$locale] ?? $features['ar'] ?? [];
        }

        return $features;
    }

    /**
     * Get Arabic features.
     */
    public function getFeaturesArAttribute(): array
    {
        $raw = $this->getRawOriginal('features');

        if (is_array($raw)) {
            $features = $raw;
        } elseif (is_string($raw)) {
            $features = json_decode($raw, true) ?? [];
        } else {
            $features = [];
        }

        if (isset($features['ar']) || isset($features['en'])) {
            return $features['ar'] ?? [];
        }

        return $features;
    }

    /**
     * Get English features.
     */
    public function getFeaturesEnAttribute(): array
    {
        $raw = $this->getRawOriginal('features');

        if (is_array($raw)) {
            $features = $raw;
        } elseif (is_string($raw)) {
            $features = json_decode($raw, true) ?? [];
        } else {
            $features = [];
        }

        if (isset($features['ar']) || isset($features['en'])) {
            return $features['en'] ?? [];
        }

        return [];
    }

    /**
     * Get limit value
     */
    public function getLimit(string $key, $default = null)
    {
        return $this->limits[$key] ?? $default;
    }

    /**
     * Check if plan has feature
     */
    public function hasFeature(string $feature): bool
    {
        return in_array($feature, $this->features ?? []);
    }
}

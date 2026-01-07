<?php

namespace App\Models\Credits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SmsPackage extends Model
{
    protected $fillable = [
        'name',
        'name_ar',
        'credits',
        'price',
        'is_active',
        'is_popular',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'is_active' => 'boolean',
            'is_popular' => 'boolean',
        ];
    }

    public function purchases(): HasMany
    {
        return $this->hasMany(SmsPurchase::class);
    }

    /**
     * Get price per SMS.
     */
    public function getPricePerSmsAttribute(): float
    {
        return $this->credits > 0 ? round($this->price / $this->credits, 4) : 0;
    }

    /**
     * Scope for active packages.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)->orderBy('sort_order');
    }
}

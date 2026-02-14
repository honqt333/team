<?php

namespace App\Models;

use App\Models\Concerns\CenterScoped;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Service extends Model
{
    use HasFactory, CenterScoped, SoftDeletes;

    // Service types
    public const TYPE_INTERNAL = 'internal';
    public const TYPE_EXTERNAL = 'external';
    public const TYPE_PACKAGE = 'package';

    protected $fillable = [
        'tenant_id',
        'center_id',
        'department_id',
        'name_ar',
        'name_en',
        'description_ar',
        'description_en',
        'base_price',
        'min_price',
        'default_discount_type',
        'default_discount_value',
        'allow_price_override',
        'duration_value',
        'duration_unit',
        'warranty_value',
        'warranty_unit',
        'type',
        'requires_approval',
        'is_active',
        'sort_order',
        'updated_by',
    ];

    protected $casts = [
        'base_price' => 'decimal:2',
        'min_price' => 'decimal:2',
        'default_discount_value' => 'decimal:2',
        'allow_price_override' => 'boolean',
        'duration_value' => 'integer',
        'warranty_value' => 'integer',
        'requires_approval' => 'boolean',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    protected $appends = ['name', 'description'];

    /**
     * Get localized name based on current locale
     */
    public function getNameAttribute(): string
    {
        $locale = app()->getLocale();
        return $locale === 'en' ? ($this->name_en ?: $this->name_ar) : ($this->name_ar ?: $this->name_en);
    }

    /**
     * Get localized description based on current locale
     */
    public function getDescriptionAttribute(): ?string
    {
        $locale = app()->getLocale();
        return $locale === 'en' ? ($this->description_en ?: $this->description_ar) : ($this->description_ar ?: $this->description_en);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function center(): BelongsTo
    {
        return $this->belongsTo(Center::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * Get the user who last updated this service
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Scope to get only active services
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to filter by type
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope for internal services only
     */
    public function scopeInternal($query)
    {
        return $query->where('type', self::TYPE_INTERNAL);
    }

    /**
     * Scope to order by sort_order
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name_ar');
    }

    /**
     * Check if service is internal
     */
    public function isInternal(): bool
    {
        return $this->type === self::TYPE_INTERNAL;
    }

    /**
     * Check if service is external
     */
    public function isExternal(): bool
    {
        return $this->type === self::TYPE_EXTERNAL;
    }

    /**
     * Get formatted duration
     */
    public function getFormattedDurationAttribute(): ?string
    {
        if (!$this->estimated_minutes) {
            return null;
        }

        $hours = floor($this->estimated_minutes / 60);
        $minutes = $this->estimated_minutes % 60;

        if ($hours > 0 && $minutes > 0) {
            return "{$hours}س {$minutes}د";
        } elseif ($hours > 0) {
            return "{$hours}س";
        }
        return "{$minutes}د";
    }

    public function items()
    {
        return $this->belongsToMany(Service::class, 'service_items', 'parent_service_id', 'child_service_id')
            ->withPivot('quantity')
            ->withTimestamps();
    }
}

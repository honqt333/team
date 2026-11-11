<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\TenantScoped;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Part extends Model
{
    use HasFactory, SoftDeletes, TenantScoped;

    protected static function boot(): void
    {
        parent::boot();

        static::saving(function (Part $part) {
            if (empty($part->purchase_conversion_factor) || (float) $part->purchase_conversion_factor <= 0) {
                $part->purchase_conversion_factor = 1.0;
            }
        });
    }

    protected $fillable = [
        'tenant_id',
        'sku',
        'barcode',
        'name_ar',
        'name_en',
        'unit_id',
        'purchase_unit_id',
        'purchase_conversion_factor',
        'category_id',
        'description',
        'image_path',
        'is_active',
        'default_sale_price',
        'min_sale_price',
        'min_qty',
        'reorder_qty',
    ];

    protected $appends = ['image_url', 'name'];

    public function getImageUrlAttribute()
    {
        // Safely check if the column exists in the attributes to avoid crashes if migration hasn't run
        $imagePath = array_key_exists('image_path', $this->attributes) ? $this->attributes['image_path'] : null;

        return $imagePath
            ? asset('storage/'.$imagePath)
            : 'https://ui-avatars.com/api/?name='.urlencode($this->name_ar).'&color=7F9CF5&background=EBF4FF';
    }

    protected $casts = [
        'is_active' => 'boolean',
        'default_sale_price' => 'decimal:2',
        'min_sale_price' => 'decimal:2',
        'min_qty' => 'decimal:3',
        'reorder_qty' => 'decimal:3',
        'purchase_conversion_factor' => 'decimal:4',
    ];

    /**
     * Ensure purchase_conversion_factor is never null or non-positive.
     */
    public function setPurchaseConversionFactorAttribute($value): void
    {
        $this->attributes['purchase_conversion_factor'] = (empty($value) || (float) $value <= 0)
            ? 1.0
            : (float) $value;
    }

    // ─────────────────────────────────────────────────────────────
    // Relationships
    // ─────────────────────────────────────────────────────────────

    /** @return BelongsTo<Tenant, $this> */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /** @return BelongsTo<InventoryUnit, $this> */
    public function unit(): BelongsTo
    {
        return $this->belongsTo(InventoryUnit::class, 'unit_id');
    }

    /** @return BelongsTo<InventoryUnit, $this> */
    public function purchaseUnit(): BelongsTo
    {
        return $this->belongsTo(InventoryUnit::class, 'purchase_unit_id');
    }

    /** @return BelongsTo<InventoryCategory, $this> */
    public function category(): BelongsTo
    {
        return $this->belongsTo(InventoryCategory::class, 'category_id');
    }

    public function inventoryBalances(): HasMany
    {
        return $this->hasMany(InventoryBalance::class);
    }

    public function inventoryMoves(): HasMany
    {
        return $this->hasMany(InventoryMove::class);
    }

    public function compatibilities(): HasMany
    {
        return $this->hasMany(PartVehicleCompatibility::class, 'part_id');
    }

    // ─────────────────────────────────────────────────────────────
    // Scopes
    // ─────────────────────────────────────────────────────────────

    public function scopeForTenant($query, int $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeSearch($query, ?string $search)
    {
        if (empty($search)) {
            return $query;
        }

        return $query->where(function ($q) use ($search) {
            $q->where('sku', 'like', "%{$search}%")
                ->orWhere('barcode', 'like', "%{$search}%")
                ->orWhere('name_ar', 'like', "%{$search}%")
                ->orWhere('name_en', 'like', "%{$search}%");
        });
    }

    // ─────────────────────────────────────────────────────────────
    // Accessors
    // ─────────────────────────────────────────────────────────────

    public function getNameAttribute(): string
    {
        $locale = app()->getLocale();

        return (string) ($locale === 'ar' ? ($this->name_ar ?: $this->name_en) : ($this->name_en ?: $this->name_ar)) ?: '';
    }

    /**
     * Get total qty on hand across all warehouses.
     */
    public function getTotalQtyOnHandAttribute(): float
    {
        return (float) $this->inventoryBalances()->sum('qty_on_hand');
    }
}

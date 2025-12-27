<?php

namespace App\Models;

use App\Models\Concerns\CenterScoped;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class VehicleMake extends Model
{
    use CenterScoped, SoftDeletes;

    protected $fillable = [
        'source',
        'tenant_id',
        'center_id',
        'name_ar',
        'name_en',
        'logo_path',
        'is_active',
        'sort_order',
        'updated_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    protected $appends = ['name'];

    /**
     * Get localized name based on current locale
     */
    public function getNameAttribute(): string
    {
        $locale = app()->getLocale();
        return $locale === 'en' ? ($this->name_en ?: $this->name_ar) : ($this->name_ar ?: $this->name_en);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function center(): BelongsTo
    {
        return $this->belongsTo(Center::class);
    }

    public function models(): HasMany
    {
        return $this->hasMany(VehicleModel::class, 'make_id');
    }

    public function vehicles(): HasMany
    {
        return $this->hasMany(Vehicle::class, 'make_id');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Scope to get only active makes
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to order by sort_order
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name_ar');
    }

    /**
     * Scope to order by source: center first, then tenant, then system
     */
    public function scopeOrderedBySource($query)
    {
        return $query->orderByRaw("FIELD(source, 'center', 'tenant', 'system')");
    }

    /**
     * Scope to get data visible to the current context.
     * Includes: system data + tenant data + center data for current context.
     */
    public function scopeVisibleToContext($query)
    {
        $tenantId = \App\Support\TenancyContext::tenantId();
        $centerId = \App\Support\TenancyContext::centerId();

        return $query->where(function ($q) use ($tenantId, $centerId) {
            // System data (always visible)
            $q->where('source', 'system');
            
            // Tenant data (visible to all centers of this tenant)
            if ($tenantId) {
                $q->orWhere(function ($sub) use ($tenantId) {
                    $sub->where('source', 'tenant')
                        ->where('tenant_id', $tenantId);
                });
            }
            
            // Center data (visible only to this center)
            if ($tenantId && $centerId) {
                $q->orWhere(function ($sub) use ($tenantId, $centerId) {
                    $sub->where('source', 'center')
                        ->where('tenant_id', $tenantId)
                        ->where('center_id', $centerId);
                });
            }
        });
    }
}

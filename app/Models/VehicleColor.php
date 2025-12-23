<?php

namespace App\Models;

use App\Models\Concerns\CenterScoped;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class VehicleColor extends Model
{
    use CenterScoped, SoftDeletes;

    protected $fillable = [
        'source',
        'tenant_id',
        'center_id',
        'name_ar',
        'name_en',
        'hex_code',
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

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Scope to get only active colors
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
            $q->where('source', 'system');
            
            if ($tenantId) {
                $q->orWhere(function ($sub) use ($tenantId) {
                    $sub->where('source', 'tenant')
                        ->where('tenant_id', $tenantId);
                });
            }
            
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

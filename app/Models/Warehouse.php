<?php

namespace App\Models;

use App\Models\Concerns\CenterScoped;
use App\Support\TenancyContext;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Warehouse extends Model
{
    use CenterScoped, HasFactory, SoftDeletes;

    protected $fillable = [
        'center_id',
        'tenant_id',
        'name',
        'code',
        'is_default',
        'is_active',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'is_active' => 'boolean',
    ];

    // ─────────────────────────────────────────────────────────────
    // Relationships
    // ─────────────────────────────────────────────────────────────

    public function center(): BelongsTo
    {
        return $this->belongsTo(Center::class);
    }

    public function inventoryBalances(): HasMany
    {
        return $this->hasMany(InventoryBalance::class);
    }

    public function inventoryMoves(): HasMany
    {
        return $this->hasMany(InventoryMove::class);
    }

    // ─────────────────────────────────────────────────────────────
    // Scopes
    // ─────────────────────────────────────────────────────────────

    public function scopeForCenter($query, int $centerId)
    {
        return $query->where('center_id', $centerId);
    }

    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // ─────────────────────────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────────────────────────

    /**
     * Get or create default warehouse for a center.
     */
    public static function getOrCreateDefault(int $centerId): self
    {
        // Need tenant context to bypass CenterScoped global scope.
        $tenantId = TenancyContext::tenantId()
            ?? Center::query()->withoutGlobalScopes()->whereKey($centerId)->value('tenant_id');

        $warehouse = static::query()
            ->withoutGlobalScopes()
            ->where('center_id', $centerId)
            ->where('is_default', true)
            ->first();

        if (! $warehouse) {
            $warehouse = new static;
            $warehouse->center_id = $centerId;
            $warehouse->tenant_id = $tenantId;
            $warehouse->name = 'المستودع الرئيسي';
            $warehouse->code = 'MAIN';
            $warehouse->is_default = true;
            $warehouse->is_active = true;
            $warehouse->save();
        }

        return $warehouse;
    }
}

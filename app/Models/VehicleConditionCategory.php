<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\CenterScoped;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class VehicleConditionCategory extends Model
{
    use CenterScoped, SoftDeletes;

    protected $fillable = [
        'source',
        'tenant_id',
        'center_id',
        'name_ar',
        'name_en',
        'is_active',
        'sort_order',
        'updated_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    protected $appends = ['name'];

    public function getNameAttribute(): string
    {
        $locale = app()->getLocale();

        return $locale === 'en' ? ($this->name_en ?: $this->name_ar) : ($this->name_ar ?: $this->name_en);
    }

    public function items(): HasMany
    {
        return $this->hasMany(VehicleConditionItem::class, 'category_id')->ordered();
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

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name_ar');
    }

    public function scopeOrderedBySource($query)
    {
        if (DB::getDriverName() === 'sqlite') {
            return $query->orderByRaw("case source when 'center' then 1 when 'tenant' then 2 when 'system' then 3 else 4 end");
        }

        return $query->orderByRaw("FIELD(source, 'center', 'tenant', 'system')");
    }
}

<?php

namespace App\Models;

use App\Models\Concerns\CenterScoped;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class InspectionTemplate extends Model
{
    use HasFactory, SoftDeletes, CenterScoped;

    protected $fillable = [
        'tenant_id',
        'center_id',
        'name',
        'description',
        'is_active',
    ];

    protected $casts = [
        'name' => 'array',
        'description' => 'array',
        'is_active' => 'boolean',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(InspectionItem::class, 'template_id')->orderBy('sort_order');
    }

    public function inspections(): HasMany
    {
        return $this->hasMany(WorkOrderInspection::class, 'template_id');
    }
}

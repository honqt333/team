<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\TenantScoped;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PartVehicleCompatibility extends Model
{
    use HasFactory, TenantScoped;

    protected $fillable = [
        'tenant_id',
        'part_id',
        'vehicle_make_id',
        'vehicle_model_id',
        'year_from',
        'year_to',
        'notes',
    ];

    protected $casts = [
        'year_from' => 'integer',
        'year_to' => 'integer',
    ];

    /** @return BelongsTo<Tenant, $this> */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /** @return BelongsTo<Part, $this> */
    public function part(): BelongsTo
    {
        return $this->belongsTo(Part::class, 'part_id');
    }

    /** @return BelongsTo<VehicleMake, $this> */
    public function make(): BelongsTo
    {
        return $this->belongsTo(VehicleMake::class, 'vehicle_make_id')->withoutGlobalScope('center_scoped');
    }

    /** @return BelongsTo<VehicleModel, $this> */
    public function model(): BelongsTo
    {
        return $this->belongsTo(VehicleModel::class, 'vehicle_model_id')->withoutGlobalScope('center_scoped');
    }
}

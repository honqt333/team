<?php

namespace App\Traits;

use App\Models\Tenant;
use App\Models\Center;
use App\Models\Customer;
use App\Models\VehicleMake;
use App\Models\VehicleModel;
use App\Models\WorkOrder;
use App\Models\Quote;
use App\Models\VehicleMileageLog;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

trait HasVehicleRelations
{
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function center(): BelongsTo
    {
        return $this->belongsTo(Center::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function make(): BelongsTo
    {
        return $this->belongsTo(VehicleMake::class, 'make_id');
    }

    public function model(): BelongsTo
    {
        return $this->belongsTo(VehicleModel::class, 'model_id');
    }

    public function workOrders(): HasMany
    {
        return $this->hasMany(WorkOrder::class);
    }

    public function quotes(): HasMany
    {
        return $this->hasMany(Quote::class);
    }

    public function mileageLogs(): HasMany
    {
        return $this->hasMany(VehicleMileageLog::class);
    }

    public function latestMileageLog(): HasOne
    {
        return $this->hasOne(VehicleMileageLog::class)->latestOfMany('recorded_at');
    }
}

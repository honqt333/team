<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Casts\Attribute;
use App\Models\Concerns\CenterScoped;

class Vehicle extends Model
{
    use HasFactory, CenterScoped;

    protected $fillable = [
        'tenant_id',
        'center_id',
        'customer_id',
        'plate_number',
        'make_id',
        'model_id',
        'make_other',
        'model_other',
        'year',
        'color',
        'vin',
        'odometer',
        'allow_lower_mileage',
        'notes',
    ];

    protected $casts = [
        'allow_lower_mileage' => 'boolean',
    ];

    protected $appends = ['display_make', 'display_model', 'display_name'];

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

    /**
     * Get the display name of the make (from system or custom).
     */
    protected function displayMake(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->make?->name ?? $this->make_other ?? null,
        );
    }

    /**
     * Get the display name of the model (from system or custom).
     */
    protected function displayModel(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->model?->name ?? $this->model_other ?? null,
        );
    }

    /**
     * Get a combined display name: "Make Model (Year)" or plate if unavailable.
     */
    protected function displayName(): Attribute
    {
        return Attribute::make(
            get: function () {
                $parts = array_filter([
                    $this->display_make,
                    $this->display_model,
                ]);
                
                $name = implode(' ', $parts);
                
                if ($this->year) {
                    $name .= " ({$this->year})";
                }
                
                return $name ?: $this->plate_number;
                return $name ?: $this->plate_number;
            },
        );
    }

    public function workOrders(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(WorkOrder::class);
    }

    public function quotes(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Quote::class);
    }

    public function mileageLogs(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(VehicleMileageLog::class);
    }

    public function latestMileageLog(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(VehicleMileageLog::class)->latestOfMany('recorded_at');
    }
}

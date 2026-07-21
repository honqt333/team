<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\TenantScoped;
use App\Support\TenancyContext;
use App\Traits\HasVehicleRelations;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Vehicle extends Model
{
    use HasFactory, HasVehicleRelations, SoftDeletes, TenantScoped;

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (Model $model) {
            if (empty($model->center_id) && $centerId = TenancyContext::centerId()) {
                $model->center_id = $centerId;
            }
        });
    }

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
}

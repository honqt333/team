<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class VehicleMileageLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'vehicle_id',
        'mileage',
        'previous_mileage',
        'difference',
        'reference_type',
        'reference_id',
        'reference_code',
        'created_by',
        'recorded_at',
    ];

    protected $casts = [
        'mileage' => 'integer',
        'previous_mileage' => 'integer',
        'difference' => 'integer',
        'recorded_at' => 'datetime',
    ];

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function reference(): MorphTo
    {
        return $this->morphTo();
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeForVehicle($query, $vehicleId)
    {
        return $query->where('vehicle_id', $vehicleId)->orderByDesc('recorded_at');
    }
}

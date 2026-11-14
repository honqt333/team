<?php

declare(strict_types=1);

namespace App\Models\HR;

use App\Models\Center;
use App\Models\Concerns\CenterScoped;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// @bypass-tenancy-scanner - Scoped via center_id + tenant_id columns
class AttendanceSettings extends Model
{
    use CenterScoped;

    protected $table = 'hr_attendance_settings';

    protected $fillable = [
        'center_id',
        'tenant_id',
        'grace_period_minutes',
        'late_deduction_per_minute',
        'absence_deduction_value',
        'absence_deduction_type',
        'overtime_rate_per_hour',
        'overtime_enabled',
        'auto_mark_absent',
        'absence_cutoff_time',
        'working_days',
        'geofence_enabled',
        'geofence_latitude',
        'geofence_longitude',
        'geofence_radius_meters',
    ];

    protected $casts = [
        'grace_period_minutes' => 'integer',
        'late_deduction_per_minute' => 'decimal:2',
        'absence_deduction_value' => 'decimal:2',
        'overtime_rate_per_hour' => 'decimal:2',
        'overtime_enabled' => 'boolean',
        'auto_mark_absent' => 'boolean',
        'absence_cutoff_time' => 'datetime:H:i',
        'working_days' => 'array',
        'geofence_enabled' => 'boolean',
        'geofence_latitude' => 'float',
        'geofence_longitude' => 'float',
        'geofence_radius_meters' => 'integer',
    ];

    public function center(): BelongsTo
    {
        return $this->belongsTo(Center::class);
    }

    /**
     * Get or create settings for a center
     */
    public static function getForCenter(int $centerId): self
    {
        $existing = self::withoutGlobalScopes()->where('center_id', $centerId)->first();

        if ($existing) {
            return $existing;
        }

        $center = Center::withoutGlobalScopes()->find($centerId);

        return self::withoutGlobalScopes()->create([
            'center_id' => $centerId,
            'tenant_id' => $center?->tenant_id,
            'grace_period_minutes' => 10,
            'late_deduction_per_minute' => 0,
            'absence_deduction_value' => 100, // 100% of daily salary
            'absence_deduction_type' => 'percentage',
            'overtime_rate_per_hour' => 0,
            'overtime_enabled' => true,
            'auto_mark_absent' => false,
            'working_days' => [0, 1, 2, 3, 4], // Sunday to Thursday
        ]);
    }

    /**
     * Check if a day is a working day
     */
    public function isWorkingDay(int $dayOfWeek): bool
    {
        $workingDays = $this->working_days ?? [0, 1, 2, 3, 4];

        return in_array($dayOfWeek, $workingDays);
    }

    /**
     * Resolve configured geofence coordinates (settings first, then center address fallback).
     *
     * @return array{lat: float, lng: float}|null
     */
    public function resolvedCoordinates(): ?array
    {
        if ($this->geofence_latitude !== null && $this->geofence_longitude !== null) {
            return ['lat' => (float) $this->geofence_latitude, 'lng' => (float) $this->geofence_longitude];
        }

        $address = $this->center?->address;

        if ($address && $address->latitude !== null && $address->longitude !== null) {
            return ['lat' => (float) $address->latitude, 'lng' => (float) $address->longitude];
        }

        return null;
    }

    /**
     * Calculate Haversine distance in meters from the center's geofence coordinates.
     */
    public function distanceMetersFrom(float $latitude, float $longitude): ?float
    {
        $coords = $this->resolvedCoordinates();

        if (! $coords) {
            return null;
        }

        $earthRadius = 6371000.0;
        $latFrom = deg2rad($coords['lat']);
        $lonFrom = deg2rad($coords['lng']);
        $latTo = deg2rad($latitude);
        $lonTo = deg2rad($longitude);

        $latDelta = $latTo - $latFrom;
        $lonDelta = $lonTo - $lonFrom;

        $angle = 2 * asin(sqrt(
            (sin($latDelta / 2) ** 2)
            + cos($latFrom) * cos($latTo) * (sin($lonDelta / 2) ** 2)
        ));

        return round($angle * $earthRadius, 2);
    }
}

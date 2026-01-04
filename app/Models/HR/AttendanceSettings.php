<?php

namespace App\Models\HR;

use App\Models\Center;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceSettings extends Model
{
    protected $table = 'hr_attendance_settings';

    protected $fillable = [
        'center_id',
        'grace_period_minutes',
        'late_deduction_per_minute',
        'absence_deduction_value',
        'absence_deduction_type',
        'overtime_rate_per_hour',
        'overtime_enabled',
        'auto_mark_absent',
        'absence_cutoff_time',
        'working_days',
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
        return self::firstOrCreate(
            ['center_id' => $centerId],
            [
                'grace_period_minutes' => 10,
                'late_deduction_per_minute' => 0,
                'absence_deduction_value' => 100, // 100% of daily salary
                'absence_deduction_type' => 'percentage',
                'overtime_rate_per_hour' => 0,
                'overtime_enabled' => true,
                'auto_mark_absent' => false,
                'working_days' => [0, 1, 2, 3, 4], // Sunday to Thursday
            ]
        );
    }

    /**
     * Check if a day is a working day
     */
    public function isWorkingDay(int $dayOfWeek): bool
    {
        $workingDays = $this->working_days ?? [0, 1, 2, 3, 4];
        return in_array($dayOfWeek, $workingDays);
    }
}

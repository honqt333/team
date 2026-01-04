<?php

namespace App\Services\HR;

use App\Models\HR\AttendanceSettings;
use App\Models\HR\Leave;
use Carbon\Carbon;

class AttendanceCalculationService
{
    protected AttendanceSettings $settings;

    public function __construct(int $centerId)
    {
        $this->settings = AttendanceSettings::getForCenter($centerId);
    }

    /**
     * Calculate late minutes with grace period
     */
    public function calculateLateMinutes(string $checkIn, string $shiftStart): int
    {
        $checkInTime = Carbon::parse($checkIn);
        $shiftStartTime = Carbon::parse($shiftStart);
        
        // Add grace period to shift start
        $graceEnd = $shiftStartTime->copy()->addMinutes($this->settings->grace_period_minutes);
        
        if ($checkInTime->lte($graceEnd)) {
            return 0; // Within grace period
        }
        
        return $checkInTime->diffInMinutes($shiftStartTime);
    }

    /**
     * Calculate early leave minutes
     */
    public function calculateEarlyLeaveMinutes(?string $checkOut, string $shiftEnd): int
    {
        if (!$checkOut) {
            return 0;
        }
        
        $checkOutTime = Carbon::parse($checkOut);
        $shiftEndTime = Carbon::parse($shiftEnd);
        
        if ($checkOutTime->gte($shiftEndTime)) {
            return 0; // Left on time or later
        }
        
        return $shiftEndTime->diffInMinutes($checkOutTime);
    }

    /**
     * Calculate overtime minutes
     */
    public function calculateOvertimeMinutes(?string $checkOut, string $shiftEnd): int
    {
        if (!$checkOut || !$this->settings->overtime_enabled) {
            return 0;
        }
        
        $checkOutTime = Carbon::parse($checkOut);
        $shiftEndTime = Carbon::parse($shiftEnd);
        
        if ($checkOutTime->lte($shiftEndTime)) {
            return 0; // Left on time or early
        }
        
        return $checkOutTime->diffInMinutes($shiftEndTime);
    }

    /**
     * Check if employee is on approved leave
     */
    public function isEmployeeOnLeave(int $employeeId, Carbon $date): bool
    {
        return Leave::where('employee_id', $employeeId)
            ->where('status', 'approved')
            ->where('start_date', '<=', $date->toDateString())
            ->where('end_date', '>=', $date->toDateString())
            ->exists();
    }

    /**
     * Get leave details for employee on a specific date
     */
    public function getEmployeeLeave(int $employeeId, Carbon $date): ?Leave
    {
        return Leave::where('employee_id', $employeeId)
            ->where('status', 'approved')
            ->where('start_date', '<=', $date->toDateString())
            ->where('end_date', '>=', $date->toDateString())
            ->first();
    }

    /**
     * Calculate late deduction amount
     */
    public function calculateLateDeduction(int $lateMinutes): float
    {
        return round($lateMinutes * $this->settings->late_deduction_per_minute, 2);
    }

    /**
     * Calculate absence deduction amount
     */
    public function calculateAbsenceDeduction(): float
    {
        return $this->settings->absence_deduction_per_day;
    }

    /**
     * Calculate overtime payment
     */
    public function calculateOvertimePayment(int $overtimeMinutes): float
    {
        $hours = $overtimeMinutes / 60;
        return round($hours * $this->settings->overtime_rate_per_hour, 2);
    }

    /**
     * Check if it's a working day
     */
    public function isWorkingDay(Carbon $date): bool
    {
        return $this->settings->isWorkingDay($date->dayOfWeek);
    }

    /**
     * Get settings
     */
    public function getSettings(): AttendanceSettings
    {
        return $this->settings;
    }
}

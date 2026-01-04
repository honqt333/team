<?php

namespace App\Policies\HR;

use App\Models\HR\Attendance;
use App\Models\User;
use App\Support\Permissions;

class AttendancePolicy
{
    /**
     * Determine whether the user can view any attendance records.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo(Permissions::HR_ATTENDANCE_VIEW);
    }

    /**
     * Determine whether the user can view the attendance record.
     */
    public function view(User $user, Attendance $attendance): bool
    {
        if (!$user->hasPermissionTo(Permissions::HR_ATTENDANCE_VIEW)) {
            return false;
        }

        // Tenant isolation
        return $attendance->tenant_id === $user->tenant_id;
    }

    /**
     * Determine whether the user can create attendance records.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo(Permissions::HR_ATTENDANCE_MANAGE);
    }

    /**
     * Determine whether the user can update the attendance record.
     */
    public function update(User $user, Attendance $attendance): bool
    {
        if (!$user->hasPermissionTo(Permissions::HR_ATTENDANCE_MANAGE)) {
            return false;
        }

        // Tenant isolation
        return $attendance->tenant_id === $user->tenant_id;
    }

    /**
     * Determine whether the user can delete the attendance record.
     */
    public function delete(User $user, Attendance $attendance): bool
    {
        if (!$user->hasPermissionTo(Permissions::HR_ATTENDANCE_MANAGE)) {
            return false;
        }

        // Tenant isolation
        return $attendance->tenant_id === $user->tenant_id;
    }
}

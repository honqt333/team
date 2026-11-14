<?php

declare(strict_types=1);

namespace App\Policies\HR;

use App\Models\HR\Attendance;
use App\Models\User;
use App\Support\TenancyContext;

class AttendancePolicy
{
    private function canAccessRecord(User $user, Attendance $attendance): bool
    {
        if ((int) $user->tenant_id !== (int) $attendance->tenant_id) {
            return false;
        }

        if ($user->hasRole('super_admin')) {
            return true;
        }

        $activeCenterId = TenancyContext::centerId() ?? $user->current_center_id;

        return $attendance->center_id === null
            || ($activeCenterId !== null && (int) $attendance->center_id === (int) $activeCenterId);
    }

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('hr.attendance.view') || $user->can('hr.attendance.manage');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Attendance $attendance): bool
    {
        return $this->canAccessRecord($user, $attendance)
            && ($user->can('hr.attendance.view') || $user->can('hr.attendance.manage'));
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('hr.attendance.manage');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Attendance $attendance): bool
    {
        return $this->canAccessRecord($user, $attendance)
            && $user->can('hr.attendance.manage');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Attendance $attendance): bool
    {
        return $this->canAccessRecord($user, $attendance)
            && $user->can('hr.attendance.manage');
    }

    /**
     * Determine whether the user can view their own attendance (Employee Portal).
     */
    public function viewOwn(User $user): bool
    {
        return $user->can('portal.attendance.view') && $user->employee !== null;
    }

    /**
     * Determine whether the user can clock in/out (Employee Portal).
     */
    public function clockInOut(User $user): bool
    {
        return $user->can('portal.attendance.clock') && $user->employee !== null;
    }
}

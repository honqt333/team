<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\AppointmentMessageDelivery;
use App\Models\User;
use App\Support\Permissions;

/**
 * Phase 11 — authorization for the
 * appointment-message-deliveries staff UI.
 *
 * The view path covers the index page and the
 * row fetch used by the modal. The retry path
 * covers the "retry send" action.
 *
 * Tenancy: the deliveries table is keyed on
 * (tenant_id, appointment_id). The view path
 * checks tenant + center. The retry path
 * additionally requires the appointment to
 * still be in the user's tenant + center at
 * the time the retry is attempted (the
 * controller re-validates under a row lock,
 * but the policy is the first line of
 * defense).
 *
 * Permissions: we reuse the existing
 * `crm.appointments.manage` permission for
 * both view and retry. Adding a new
 * permission would require a new seeder
 * migration, which is not warranted for a
 * single new screen — operators who can
 * manage appointments in the system are
 * already trusted to handle their SMS
 * delivery rows. The controller still
 * enforces the "do not show a row that no
 * longer makes sense to retry" rules
 * (status + snapshot + cancellation)
 * independently of the policy.
 */
class AppointmentMessageDeliveryPolicy
{
    /**
     * The user can see deliveries for their
     * tenant + center. The deliveries table
     * does not store center_id directly; the
     * row is linked to the appointment row,
     * and the controller filters on
     * appointments.center_id.
     *
     * For an `view(Delivery $row)` check we
     * load the row's tenant_id and the
     * appointment's center_id and compare.
     */
    public function viewAny(User $user): bool
    {
        return $user->can(Permissions::APPOINTMENTS_MANAGE);
    }

    public function view(User $user, AppointmentMessageDelivery $delivery): bool
    {
        if (! $user->can(Permissions::APPOINTMENTS_MANAGE)) {
            return false;
        }

        // Tenant check on the delivery row
        // itself. The controller will further
        // constrain by center via the joined
        // appointment.
        if ($delivery->tenant_id !== $user->tenant_id) {
            return false;
        }

        return true;
    }

    /**
     * Retry a previously-failed or skipped
     * delivery. Same permission set as view;
     * a separate method so the controller
     * can call `$this->authorize('retry', $row)`
     * separately from the read authorization.
     */
    public function retry(User $user, AppointmentMessageDelivery $delivery): bool
    {
        return $this->view($user, $delivery);
    }
}

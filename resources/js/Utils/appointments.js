/**
 * The states an appointment can be marked "no show" from.
 *
 * The server refuses the transition from anything else: the booking is
 * already checked in, or it was cancelled, converted or already marked
 * as no-show. The button on the appointments page used to be shown for
 * every state except those three — so it appeared on an appointment
 * that had been checked in, and pressing it produced a 422 that the
 * page never showed. The button looked broken.
 *
 * The list here mirrors `AppointmentSchedulingService::markNoShow()`.
 */
export const NO_SHOW_ALLOWED_STATUSES = ['scheduled', 'confirmed'];

/**
 * May this appointment be marked as no-show?
 */
export const canMarkNoShow = (appointment) =>
    NO_SHOW_ALLOWED_STATUSES.includes(appointment?.status);

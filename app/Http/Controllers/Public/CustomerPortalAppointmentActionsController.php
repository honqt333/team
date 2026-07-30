<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Http\Middleware\PortalLocale;
use App\Models\Appointment;
use App\Models\Center;
use App\Models\Customer;
use App\Models\Service;
use App\Models\Tenant;
use App\Models\TenantWebsite;
use App\Services\CustomerPortal\CustomerPortalAppointmentActionService;
use App\Services\CustomerPortal\CustomerPortalAvailabilityService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Self-service cancel + reschedule for the customer portal.
 *
 * The endpoints mount under the same `customer.portal.session`
 * middleware as the booking flow, so `portal_customer` and
 * `public_tenant` are always populated by the time we run.
 *
 * Two routes:
 *
 *   GET  /{tenant}/portal/appointments/{id}/edit
 *   POST /{tenant}/portal/appointments/{id}/cancel
 *   POST /{tenant}/portal/appointments/{id}/reschedule/availability
 *   POST /{tenant}/portal/appointments/{id}/reschedule
 *
 * The middlewares the chain has already run by the time
 * we get here:
 *   - ResolveTenantFromUrl  → public_tenant attribute
 *   - EnsureCustomerPortalSession → portal_customer attribute
 *
 * The two-hour minimum window is checked at the top of
 * every action and in the scheduler as defense in depth.
 */
class CustomerPortalAppointmentActionsController extends Controller
{
    /** Hours before scheduled_at where the customer can still act. */
    public const MIN_HOURS_BEFORE = 2;

    /** Max booking window. Same constant as the availability service. */
    public const MAX_LOOKAHEAD_DAYS = 14;

    /** Max characters for the optional cancellation reason. */
    public const CANCEL_REASON_MAX = 255;

    public function __construct(
        private readonly CustomerPortalAvailabilityService $availability,
        private readonly CustomerPortalAppointmentActionService $actions,
    ) {}

    /**
     * Render the reschedule wizard for an eligible
     * appointment. The form receives:
     *
     *   - the original center (the customer can pick
     *     a different one, but the services stay the
     *     same as the existing appointment)
     *   - the list of active centers in the tenant
     *     (the same set the booking wizard shows)
     *   - the services that were originally booked,
     *     with their current durations from the
     *     service rows (the wizard never trusts a
     *     duration from the client)
     *   - the appointment's current scheduled_at,
     *     as the initial date on the picker
     *
     * The wizard then asks for a new center and a new
     * slot, hitting `availability` and `reschedule` as
     * the customer advances.
     */
    public function showReschedule(Request $request): Response
    {
        $id = (int) $request->route('id');
        $tenant = $this->tenant($request);
        $customer = $this->customer($request);

        // Use the action service's read-only load.
        // It does the same tenant + customer lookup +
        // 2h + status check (without the row lock).
        $appointment = $this->actions->loadForEdit(
            tenant: $tenant,
            customer: $customer,
            appointmentId: $id,
        );

        $centers = Center::query()
            ->where('tenant_id', $tenant->id)
            ->where('is_active', true)
            ->orderBy('is_main', 'desc')
            ->orderBy('name_ar')
            ->get(['id', 'name', 'name_ar', 'name_en', 'is_main']);

        $services = Service::query()
            ->where('tenant_id', $tenant->id)
            ->where('is_active', true)
            ->where('type', Service::TYPE_INTERNAL)
            ->whereIn('id', $appointment->services->pluck('id'))
            ->orderBy('sort_order')
            ->orderBy('name_ar')
            ->get(['id', 'center_id', 'name_ar', 'name_en', 'description_ar', 'description_en', 'duration_value', 'duration_unit']);

        return Inertia::render('Public/Portal/Appointments/Reschedule', [
            'tenant' => $this->tenantContext($request),
            'locale' => $this->localeContext($request),
            'appointment' => [
                'id' => $appointment->id,
                'center_id' => $appointment->center_id,
                'center_name' => $appointment->center?->display_name,
                'scheduled_at' => $appointment->scheduled_at?->toIso8601String(),
                'estimated_end_at' => $appointment->estimated_end_at?->toIso8601String(),
                'services' => $appointment->services->map(fn (Service $s) => [
                    'id' => $s->id,
                    'name' => (string) ($s->name_ar ?: $s->name_en),
                    'center_id' => $s->center_id,
                    'duration_minutes' => $this->serviceMinutes($s),
                ])->values(),
            ],
            'centers' => $centers->map(fn (Center $c) => [
                'id' => $c->id,
                'name' => $c->display_name,
                'is_main' => (bool) $c->is_main,
            ])->values(),
            'services' => $services->map(fn (Service $s) => [
                'id' => $s->id,
                'center_id' => $s->center_id,
                'name' => (string) ($s->name_ar ?: $s->name_en),
                'description' => (string) ($s->description_ar ?: $s->description_en),
                'duration_minutes' => $this->serviceMinutes($s),
            ])->values(),
            'minHoursBefore' => self::MIN_HOURS_BEFORE,
        ]);
    }

    /**
     * Read-only availability for the reschedule form.
     * Same shape as the booking availability endpoint
     * but the reschedule view does not pre-fill a date
     * and the customer must keep the same services.
     */
    public function rescheduleAvailability(Request $request): JsonResponse
    {
        $id = (int) $request->route('id');
        $tenant = $this->tenant($request);
        $customer = $this->customer($request);

        // Use the action service's read-only path.
        // It does the same tenant + customer lookup +
        // 2h + status check (without the row lock —
        // this is a read for the form, the actual
        // submit re-validates under the lock).
        try {
            $appointment = $this->actions->availabilityForReschedule(
                tenant: $tenant,
                customer: $customer,
                appointmentId: $id,
            );
        } catch (NotFoundHttpException) {
            return response()->json([
                'open' => false,
                'slots' => [],
                'reason' => 'not_found',
            ], 404);
        }

        $data = $request->validate([
            'center_id' => ['required', 'integer'],
            'date' => ['required', 'date_format:Y-m-d'],
        ]);

        $centerOk = Center::query()
            ->where('tenant_id', $tenant->id)
            ->where('is_active', true)
            ->whereKey($data['center_id'])
            ->exists();

        if (! $centerOk) {
            return response()->json([
                'center_id' => (int) $data['center_id'],
                'date' => $data['date'],
                'open' => false,
                'slots' => [],
                'reason' => 'center_unavailable',
            ], 422);
        }

        // The services the customer is moving are the
        // services that were booked on the original
        // appointment. We re-verify that they all still
        // exist, are active, and are still internal —
        // a service could have been deactivated between
        // booking and reschedule.
        $originalServiceIds = $appointment->services->pluck('id')->all();
        $serviceRows = Service::query()
            ->where('tenant_id', $tenant->id)
            ->where('is_active', true)
            ->where('type', Service::TYPE_INTERNAL)
            ->whereIn('id', $originalServiceIds)
            ->get();

        if ($serviceRows->count() !== count($originalServiceIds)) {
            return response()->json([
                'center_id' => (int) $data['center_id'],
                'date' => $data['date'],
                'open' => false,
                'slots' => [],
                'reason' => 'services_unavailable',
            ], 422);
        }

        $result = $this->availability->slots(
            tenantId: $tenant->id,
            centerId: (int) $data['center_id'],
            serviceIds: $originalServiceIds,
            dateYmd: $data['date'],
        );

        return response()->json($result);
    }

    /**
     * Cancel an eligible appointment. The reason is
     * optional, up to 255 chars. We always prefix the
     * persisted reason with a portal-marker so an
     * operator reading the audit log can tell at a
     * glance that the cancellation came from the
     * customer portal, not from the staff UI.
     */
    public function cancel(Request $request): RedirectResponse
    {
        $id = (int) $request->route('id');
        $tenant = $this->tenant($request);
        $customer = $this->customer($request);

        $data = $request->validate([
            'reason' => ['nullable', 'string', 'max:'.self::CANCEL_REASON_MAX],
        ]);

        // The action service locks the appointment
        // row, re-checks tenant + customer ownership,
        // status (scheduled/confirmed only), and the
        // 2-hour window — all inside a single
        // transaction. A concurrent check-in from
        // the staff side loses the race: our
        // lockForUpdate pins the row, the staff
        // check-in waits, we read the still-scheduled
        // status, the staff check-in proceeds on the
        // same row after we commit. The order here
        // (customer cancel wins the lock first) means
        // the operator can no longer check the
        // customer in. The opposite order means
        // status flips to checked_in while we are
        // waiting, our re-check sees checked_in, and
        // we abort with 404. Both are safe; the
        // customer just gets a 404 in the latter
        // case.
        try {
            $this->actions->cancel(
                tenant: $tenant,
                customer: $customer,
                appointmentId: $id,
                reason: $data['reason'] ?? null,
            );
        } catch (NotFoundHttpException) {
            // The service signals eligibility failures
            // (wrong owner, wrong status, inside 2h
            // window, etc.) via abort(404) so we never
            // leak the underlying reason to the
            // customer. The 404 propagates straight
            // through.
            throw new NotFoundHttpException;
        } catch (RuntimeException $e) {
            // Unexpected error (DB connection drop, etc).
            return back()
                ->withInput()
                ->withErrors(['booking' => __('customer_portal.actions.errors.cancel_failed')]);
        }

        return redirect()
            ->to('/'.$tenant->slug.'/portal/dashboard')
            ->with('status', __('customer_portal.actions.cancelled_flash'));
    }

    /**
     * Reschedule an eligible appointment to a new
     * center + slot. The services and the vehicle
     * stay the same as the original appointment —
     * the customer can only change WHERE and WHEN.
     * The bay is server-picked (the customer never
     * gets to choose a bay).
     *
     * The state machine + 2h window + slot snapshot
     * are all enforced INSIDE the action service
     * under the appointment row lock. By the time
     * the scheduler takes its own bay + user +
     * vehicle locks, the row is already pinned to
     * {scheduled, confirmed} and the new time is on
     * a published slot. Concurrent staff action
     * (check-in, cancel, convert) cannot slip past.
     */

    /**
     * Reschedule an eligible appointment to a new
     * center + slot. The services and the vehicle
     * stay the same as the original appointment — the
     * customer can only change WHERE and WHEN. The
     * bay is server-picked (the customer never gets
     * to choose a bay).
     *
     * Defense-in-depth checks we run here (and which
     * AppointmentSchedulingService re-runs under a
     * row lock):
     *
     *   1. tenant + customer ownership of the
     *      appointment (404 otherwise)
     *   2. status in {scheduled, confirmed}
     *   3. ≥ MIN_HOURS_BEFORE until scheduled_at
     *   4. ≤ MAX_LOOKAHEAD_DAYS until scheduled_at
     *   5. new center is in this tenant + active
     *   6. services unchanged and still active +
     *      internal + belong to the new center
     *   7. new scheduled_at is on a slot the
     *      availability endpoint would publish
     *      (defends against off-grid minutes)
     *   8. new center working hours and duration
     *      fit the new window
     *   9. bay picked server-side, never from input
     */
    public function reschedule(Request $request): RedirectResponse
    {
        $id = (int) $request->route('id');
        $tenant = $this->tenant($request);
        $customer = $this->customer($request);

        $data = $request->validate([
            'center_id' => ['required', 'integer'],
            'scheduled_at' => ['required', 'date'],
            // We deliberately do NOT accept
            // service_ids, vehicle_id, service_bay_id,
            // assigned_user_id, status, or source. The
            // controller builds those from the existing
            // appointment row, never from the form.
        ]);

        // The action service handles EVERYTHING
        // from here: locking the row, re-checking
        // ownership + status + 2h window, re-loading
        // the new center + services, slot snapshot,
        // server-picked bay, and the actual
        // reschedule via the staff scheduler. We
        // also delegate all the failure mapping —
        // the service throws `RuntimeException` with
        // a code the caller (this controller) maps
        // to a user-friendly error message, OR calls
        // `abort(404)` for the eligibility failures
        // (we never leak the underlying reason to the
        // customer).
        try {
            $this->actions->reschedule(
                tenant: $tenant,
                customer: $customer,
                appointmentId: $id,
                newCenterId: (int) $data['center_id'],
                newStart: Carbon::parse($data['scheduled_at']),
            );
        } catch (NotFoundHttpException) {
            // Eligibility failures (wrong owner, wrong
            // status, inside 2h window, missing services,
            // etc.) surface as 404 via abort() so we
            // never leak the underlying reason.
            throw new NotFoundHttpException;
        } catch (RuntimeException $e) {
            return $this->actionError($request, $this->mapSchedulerError($e->getMessage()));
        }

        return redirect()
            ->to('/'.$tenant->slug.'/portal/appointment/'.$id)
            ->with('status', __('customer_portal.actions.rescheduled_flash'));
    }

    /**
     * Map a `RuntimeException` code from the action
     * service into a customer-friendly translation
     * key. The service throws specific codes for
     * known failure modes; anything else falls
     * through to a generic "this time is no longer
     * available" message — we never reveal an
     * internal detail.
     */
    private function mapSchedulerError(string $code): string
    {
        return match ($code) {
            'bay_conflict', 'vehicle_conflict', 'technician_conflict' => 'slot_no_longer_available',
            'start_in_past' => 'slot_out_of_window',
            'outside_working_hours', 'center_closed' => 'slot_out_of_window',
            'cross_tenant_or_center' => 'services_unavailable',
            'invalid_transition_reschedule' => 'slot_no_longer_available',
            'center_unavailable' => 'center_unavailable',
            'services_unavailable' => 'services_unavailable',
            'slot_no_longer_available' => 'slot_no_longer_available',
            'slot_out_of_window' => 'slot_out_of_window',
            'vehicle_missing' => 'slot_no_longer_available',
            default => 'slot_no_longer_available',
        };
    }

    private function actionError(Request $request, string $code): RedirectResponse
    {
        return back()
            ->withInput()
            ->withErrors(['booking' => __('customer_portal.actions.errors.'.$code)]);
    }

    private function customer(Request $request): Customer
    {
        $customer = $request->attributes->get('portal_customer');

        if (! $customer instanceof Customer) {
            abort(401);
        }

        return $customer;
    }

    private function tenant(Request $request): Tenant
    {
        $tenant = $request->attributes->get('public_tenant');

        if (! $tenant instanceof Tenant) {
            abort(404);
        }

        return $tenant;
    }

    private function tenantContext(Request $request): array
    {
        $tenant = $this->tenant($request);
        $website = TenantWebsite::where('tenant_id', $tenant->id)->first();

        return [
            'id' => $tenant->id,
            'slug' => $tenant->slug,
            'site_name' => $website?->display_name ?? $tenant->name,
            'tagline' => $website?->tagline,
            'logo_url' => $website?->logo_url,
            'has_logo' => ! empty($website?->logo_url),
            'primary_color' => $website?->effective_primary_color ?? '#4f46e5',
            'secondary_color' => $website?->effective_secondary_color ?? '#0ea5e9',
        ];
    }

    private function localeContext(Request $request): array
    {
        $current = $request->attributes->get('portal_locale') ?? PortalLocale::DEFAULT_LOCALE;

        return [
            'current' => $current,
            'is_rtl' => $current === 'ar',
        ];
    }

    private function serviceMinutes(Service $service): ?int
    {
        if (! $service->duration_value) {
            return null;
        }

        return match ($service->duration_unit ?? 'minute') {
            'hour' => (int) $service->duration_value * 60,
            'day' => (int) $service->duration_value * 60 * 24,
            default => (int) $service->duration_value,
        };
    }
}

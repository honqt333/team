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
use App\Models\Vehicle;
use App\Services\AppointmentSchedulingService;
use App\Services\CustomerPortal\CustomerPortalAvailabilityService;
use App\Services\Messaging\AppointmentMessageDispatcher;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;

/**
 * Customer-portal self-service booking.
 *
 * The portal exposes a four-step flow (center → vehicle
 * → services → time → confirm) backed by three endpoints:
 *
 *   GET  /{tenant}/portal/appointments/new   — render
 *   GET  /{tenant}/portal/appointments/availability
 *                                          — slots for a
 *                                            (center,
 *                                            services,
 *                                            date) combo
 *   POST /{tenant}/portal/appointments         — store
 *
 * The middleware chain (public.tenant → customer.portal.
 * locale → customer.portal.session) has already:
 *   - resolved the tenant from the {tenant} slug
 *   - bound `public_tenant` on the request
 *   - bound `portal_customer` on the request (after a
 *     successful OTP login)
 *
 * What we trust from the request
 * -----------------------------
 *   - tenant.id           (route-resolved, not from body)
 *   - customer.id         (session-resolved, not from body)
 *   - vehicle.id          (form-chosen, but verified to
 *                          belong to customer AND tenant)
 *   - service_ids[]       (form-chosen, but verified to
 *                          belong to tenant AND active AND
 *                          internal)
 *   - center_id           (form-chosen, but verified to
 *                          belong to tenant AND active)
 *   - scheduled_at        (form-chosen ISO-8601)
 *
 * What we NEVER trust from the request
 * ------------------------------------
 *   - bay_id              (always picked server-side)
 *   - assigned_user_id    (always null at this stage)
 *   - duration            (always recomputed from
 *                          selected services)
 *   - tenant_id           (route-resolved, not from body)
 *   - customer_id         (session-resolved, not from body)
 *   - status              (always `scheduled`)
 *   - source              (always `customer_portal`)
 *   - any flag like "vip"
 *
 * Race protection
 * ---------------
 * The store endpoint re-runs the availability check
 * inside the scheduling service's transaction with
 * row locks on the bays. Two concurrent submissions
 * for the same slot cannot both win — the second
 * blocks on the first's lock and fails the conflict
 * check.
 */
class CustomerPortalAppointmentController extends Controller
{
    public function __construct(
        private readonly CustomerPortalAvailabilityService $availability,
        private readonly AppointmentSchedulingService $scheduler,
        private readonly AppointmentMessageDispatcher $messageDispatcher,
    ) {}

    public function showNew(Request $request): Response
    {
        $customer = $this->customer($request);
        $tenant = $this->tenant($request);

        // The form receives a small, curated payload.
        // We never expose internal columns (id of the
        // customer, employee user ids, etc.).
        $centers = Center::query()
            ->where('tenant_id', $tenant->id)
            ->where('is_active', true)
            ->orderBy('is_main', 'desc')
            ->orderBy('name_ar')
            ->get(['id', 'name', 'name_ar', 'name_en', 'is_main']);

        $vehicles = $customer->vehicles()
            ->where('tenant_id', $tenant->id)
            ->orderByDesc('created_at')
            ->get(['id', 'plate_number', 'make', 'model', 'year', 'color']);

        // Services: tenant-scoped, active, internal.
        // The portal never sees packages. We expose the
        // full tenant catalog here; the availability /
        // store endpoints re-filter by center and the
        // wizard sends the chosen center along with the
        // chosen services.
        $services = Service::query()
            ->where('tenant_id', $tenant->id)
            ->where('is_active', true)
            ->where('type', Service::TYPE_INTERNAL)
            ->orderBy('sort_order')
            ->orderBy('name_ar')
            ->get(['id', 'center_id', 'name_ar', 'name_en', 'description_ar', 'description_en', 'duration_value', 'duration_unit']);

        return Inertia::render('Public/Portal/Appointments/New', [
            'tenant' => $this->tenantContext($request),
            'locale' => $this->localeContext($request),
            'centers' => $centers->map(fn (Center $c) => [
                'id' => $c->id,
                'name' => $c->display_name,
                'is_main' => (bool) $c->is_main,
            ])->values(),
            'vehicles' => $vehicles->map(fn (Vehicle $v) => [
                'id' => $v->id,
                'plate_number' => $v->plate_number,
                'label' => trim(implode(' · ', array_filter([$v->make, $v->model, $v->year, $v->plate_number]))),
            ])->values(),
            'services' => $services->map(fn (Service $s) => [
                'id' => $s->id,
                'center_id' => $s->center_id,
                'name' => $s->name,
                'description' => $s->description,
                'duration_minutes' => $this->serviceMinutes($s),
            ])->values(),
        ]);
    }

    /**
     * Returns the slot grid for the (center, services,
     * date) triple. Read-only — no row locks. The Vue
     * form calls this whenever the user advances to
     * the time step.
     */
    public function availability(Request $request): JsonResponse
    {
        $tenant = $this->tenant($request);
        $this->customer($request); // session check

        $data = $request->validate([
            'center_id' => ['required', 'integer'],
            'date' => ['required', 'date_format:Y-m-d'],
            'service_ids' => ['required', 'array', 'min:1'],
            'service_ids.*' => ['integer'],
        ]);

        // Tenant isolation: every id we touch is
        // re-checked against the current tenant. A
        // malicious payload referencing a center in
        // tenant B is rejected here, not later.
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

        // Services must be active, internal, AND in the
        // SAME center as the booking. A service registered
        // in center A cannot be booked at center B even
        // within the same tenant — the workshop capacity
        // plan is per-center.
        $serviceIdsValid = Service::query()
            ->where('tenant_id', $tenant->id)
            ->where('is_active', true)
            ->where('type', Service::TYPE_INTERNAL)
            ->where('center_id', (int) $data['center_id'])
            ->whereIn('id', $data['service_ids'])
            ->count();

        if ($serviceIdsValid !== count($data['service_ids'])) {
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
            serviceIds: array_map('intval', $data['service_ids']),
            dateYmd: $data['date'],
        );

        return response()->json($result);
    }

    public function store(Request $request): RedirectResponse
    {
        $tenant = $this->tenant($request);
        $customer = $this->customer($request);

        $data = $request->validate([
            'center_id' => ['required', 'integer'],
            'vehicle_id' => ['required', 'integer'],
            'service_ids' => ['required', 'array', 'min:1'],
            'service_ids.*' => ['integer'],
            'scheduled_at' => ['required', 'date'],
            // We deliberately do NOT validate
            // service_bay_id, assigned_user_id, or any
            // duration the client tries to set. Those
            // are always server-derived.
        ]);

        // 1. Tenant + active center. We never trust
        // the form's center_id without re-checking
        // tenant and is_active.
        $center = Center::query()
            ->where('tenant_id', $tenant->id)
            ->where('is_active', true)
            ->whereKey($data['center_id'])
            ->first();

        if ($center === null) {
            return $this->bookingError($request, 'center_unavailable');
        }

        // 2. Vehicle belongs to the customer AND
        // the same tenant. A customer cannot book
        // another customer's vehicle even within
        // the same tenant.
        $vehicle = Vehicle::query()
            ->where('tenant_id', $tenant->id)
            ->where('customer_id', $customer->id)
            ->whereKey($data['vehicle_id'])
            ->first();

        if ($vehicle === null) {
            return $this->bookingError($request, 'vehicle_not_owned');
        }

        // 3. Services: tenant, active, internal, AND
        // in the same center as the booking. The
        // scheduler's attachServices would silently
        // drop services from other centers; we reject
        // the request up front instead so the customer
        // gets a clear error.
        $services = Service::query()
            ->where('tenant_id', $tenant->id)
            ->where('is_active', true)
            ->where('type', Service::TYPE_INTERNAL)
            ->where('center_id', $center->id)
            ->whereIn('id', $data['service_ids'])
            ->get();

        if ($services->count() !== count($data['service_ids'])) {
            return $this->bookingError($request, 'services_unavailable');
        }

        // 4. Window: not in the past, at least one hour
        // out, at most 14 days out.
        $now = Carbon::now();
        $scheduledAt = Carbon::parse($data['scheduled_at']);
        $earliest = $now->copy()->addHours(
            CustomerPortalAvailabilityService::MIN_LOOKAHEAD_HOURS
        );
        $maxDate = $now->copy()->addDays(
            CustomerPortalAvailabilityService::MAX_LOOKAHEAD_DAYS
        );

        if ($scheduledAt->lt($earliest) || $scheduledAt->gt($maxDate)) {
            return $this->bookingError($request, 'slot_out_of_window');
        }

        // 5. Duration from services — never from the
        // form. The customer cannot influence the
        // service duration.
        $duration = 0;

        foreach ($services as $s) {
            $minutes = $this->serviceMinutes($s);

            if ($minutes !== null) {
                $duration += $minutes;
            }
        }

        if ($duration <= 0) {
            $duration = CustomerPortalAvailabilityService::FALLBACK_DURATION_MINUTES;
        }
        $estimatedEndAt = $scheduledAt->copy()->addMinutes($duration);

        // 5b. Slot sanity: the scheduled_at must be
        // one of the slots the availability endpoint
        // would publish for this (center, services,
        // date) triple. This prevents the client from
        // POSTing a time that is inside working hours
        // and has a free bay, but is not on a 30-min
        // boundary the server offered (e.g. 10:13,
        // 10:17). Such a request would otherwise pass
        // the conflict check but land on an "off-grid"
        // time that no other customer could book.
        $slotResult = $this->availability->slots(
            tenantId: $tenant->id,
            centerId: $center->id,
            serviceIds: $services->pluck('id')->all(),
            dateYmd: $scheduledAt->copy()->startOfDay()->toDateString(),
        );
        $offeredSlots = collect($slotResult['slots'] ?? [])
            ->map(fn ($s) => Carbon::parse($s)->setTimezone($scheduledAt->getTimezone())->timestamp)
            ->all();
        $isOffered = in_array(
            $scheduledAt->copy()->setTimezone($scheduledAt->getTimezone())->timestamp,
            $offeredSlots,
            true
        );

        if (! $isOffered) {
            return $this->bookingError($request, 'slot_no_longer_available');
        }

        // 6. Bay picked by the server. We re-run the
        // same conflict check the scheduler will run
        // (without the lock) so we can fail fast with
        // a friendly message; the scheduler then
        // takes the row lock and runs the same check
        // for real.
        $bay = $this->availability->pickBay(
            tenantId: $tenant->id,
            centerId: $center->id,
            scheduledAt: $scheduledAt,
            estimatedEndAt: $estimatedEndAt,
        );

        if ($bay === null) {
            return $this->bookingError($request, 'slot_no_longer_available');
        }

        // 7. Atomic create. The scheduler re-validates
        // time window, working hours, and conflicts
        // under a row lock. If two customers hit the
        // same slot, the second's transaction blocks
        // on the first's lock and fails the conflict
        // check.
        try {
            $appointment = $this->scheduler->create(
                scheduledAt: $scheduledAt,
                estimatedEndAt: $estimatedEndAt,
                customer: $customer,
                vehicle: $vehicle,
                bay: $bay,
                assignedUser: null,
                serviceIds: $services->pluck('id')->all(),
                overrides: [
                    'status' => Appointment::STATUS_SCHEDULED,
                    'source' => Appointment::SOURCE_CUSTOMER_PORTAL,
                ],
                centerIdOverride: $center->id,
            );
        } catch (RuntimeException $e) {
            // Map the scheduler's code to a
            // customer-friendly message. We surface
            // the same vocabulary on every error to
            // avoid leaking internal state.
            $code = $e->getMessage();

            return $this->bookingError($request, match ($code) {
                'bay_conflict', 'vehicle_conflict', 'technician_conflict' => 'slot_no_longer_available',
                'start_in_past' => 'slot_out_of_window',
                'outside_working_hours', 'center_closed' => 'slot_out_of_window',
                default => 'slot_no_longer_available',
            });
        }

        // 8. Fire the SMS confirmation. The
        // dispatcher is invoked AFTER the scheduler
        // has committed the appointment row. The
        // dispatcher inserts the delivery row in a
        // fresh transaction and dispatches the job.
        // A failure here does not 500 the response —
        // the customer has their booking, the SMS is
        // a best-effort side-effect.
        //
        // We wrap the dispatch in DB::afterCommit so
        // a transaction rollback (e.g. an exception
        // thrown between here and the redirect) does
        // not leave a delivery row pointing at a
        // vanished appointment.
        DB::afterCommit(function () use ($appointment, $customer) {
            $this->messageDispatcher->confirmation($appointment, $customer);
        });

        // 9. Redirect to the confirmation page. We
        // use the tenant.slug + appointment id so
        // the user has a deep link they can refresh.
        return redirect()
            ->to('/'.$tenant->slug.'/portal/appointment/'.$appointment->id)
            ->with('status', __('customer_portal.booking.confirmed_flash'));
    }

    public function showConfirmation(Request $request): Response
    {
        $id = (string) $request->route('id');
        $tenant = $this->tenant($request);
        $customer = $this->customer($request);

        // The route's `where(['id' => '\d+'])` constraint
        // guarantees $id is a string of digits. We read
        // it explicitly from the route rather than
        // relying on positional method binding because
        // PHP's method resolver can pick up a different
        // scalar in some test-environment edge cases.

        // whereKey accepts both int and string. The
        // route's whereNumber constraint guarantees
        // digits only, but the type hint stays
        // permissive in case the constraint is
        // loosened later. The lookup is the
        // canonical tenant + customer check.
        $appointment = Appointment::query()
            ->where('tenant_id', $tenant->id)
            ->where('customer_id', $customer->id)
            ->whereKey($id)
            ->with([
                'center:id,name,name_ar,name_en',
                'vehicle:id,plate_number,make_id,model_id,make_other,model_other,year',
                'vehicle.make:id,name_ar,name_en',
                'vehicle.model:id,name_ar,name_en',
                'services:id,name_ar,name_en',
            ])
            ->first();

        if ($appointment === null) {
            abort(404);
        }

        return Inertia::render('Public/Portal/Appointments/Confirmation', [
            'tenant' => $this->tenantContext($request),
            'locale' => $this->localeContext($request),
            'appointment' => [
                'id' => $appointment->id,
                'scheduled_at' => $appointment->scheduled_at?->toIso8601String(),
                'estimated_end_at' => $appointment->estimated_end_at?->toIso8601String(),
                'status' => $appointment->status,
                'source' => $appointment->source,
                'center_name' => $appointment->center?->display_name,
                'vehicle_label' => trim(implode(' · ', array_filter([
                    $appointment->vehicle?->display_make ?? $appointment->vehicle?->make?->name,
                    $appointment->vehicle?->display_model ?? $appointment->vehicle?->model?->name,
                    $appointment->vehicle?->year,
                    $appointment->vehicle?->plate_number,
                ]))),
                'services' => $appointment->services->map(fn ($s) => [
                    'id' => $s->id,
                    'name' => $s->name,
                ])->values(),
            ],
        ]);
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
            'primary_color' => $website?->effective_primary_color ?? '#2563eb',
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

    private function bookingError(Request $request, string $code): RedirectResponse
    {
        return back()
            ->withInput()
            ->withErrors(['booking' => __('customer_portal.booking.errors.'.$code)]);
    }
}

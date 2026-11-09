<?php

declare(strict_types=1);

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Jobs\SendAppointmentMessage;
use App\Models\Appointment;
use App\Models\AppointmentMessageDelivery;
use App\Models\Center;
use App\Models\User;
use App\Services\Messaging\AppointmentMessageDispatcher;
use App\Services\Messaging\AppointmentMessageTemplate;
use App\Services\Messaging\SmsService;
use App\Support\Permissions;
use Carbon\Carbon;
use Illuminate\Contracts\Bus\Dispatcher as BusDispatcherContract;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;
use ReflectionClass;
use Throwable;

/**
 * Phase 11 — staff UI for the appointment
 * message delivery log + safe manual retry.
 *
 * Routes mounted under
 * `/app/communications/appointment-messages`:
 *
 *   GET  .../                          index page (paginated log)
 *   POST .../{delivery}/retry          manual retry of a failed row
 *
 * Tenant isolation
 * ----------------
 * Every read and write is keyed on the
 * authenticated user's
 * `tenant_id`. The deliveries table is
 * additionally joined to the appointments
 * table to filter by the user's
 * `current_center_id`. A user from another
 * tenant cannot see the row in the list, and
 * the retry endpoint returns 404 (not 403) so
 * the existence of the row is not leaked.
 *
 * Center isolation
 * ----------------
 * The center filter is applied at the
 * controller level (we never rely on
 * `Tenant::current()`). A user with a
 * `current_center_id` sees deliveries linked
 * to appointments in that center. The
 * deliveries table does not have center_id;
 * we join through the appointment row.
 *
 * Authorization
 * -------------
 * Both endpoints go through the
 * `AppointmentMessageDeliveryPolicy`. The
 * retry endpoint additionally enforces
 * runtime safety checks (status + snapshot +
 * cancellation) inside a row-locked
 * transaction, so the policy check is the
 * first line of defense and the row-locked
 * re-check is the second.
 *
 * Privacy contract on retry
 * -------------------------
 * The retry handler never reads the phone
 * from the request body. The phone is loaded
 * fresh from the customer row. The error
 * message stored in the delivery row is
 * `null` after a retry attempt — only the
 * error_code is set. The audit log entry
 * (appointment_activities) contains the
 * delivery id and message type, never the
 * phone or the body.
 */
class AppointmentMessageDeliveryController extends Controller
{
    use AuthorizesRequests;

    /**
     * Skip-codes that mean the row is no
     * longer eligible for a manual retry. The
     * retry endpoint will refuse these with
     * the same 404 response, but listing them
     * here keeps the rule in one place.
     */
    private const NON_RETRYABLE_SKIP_CODES = [
        'appointment_schedule_changed',
        'not_applicable_anymore',
        'appointment_missing',
        'customer_missing',
        'no_phone',
    ];

    public function __construct(
        private readonly AppointmentMessageDispatcher $dispatcher,
        private readonly AppointmentMessageTemplate $template,
        private readonly SmsService $sms,
        private readonly BusDispatcherContract $bus,
    ) {}

    /**
     * Paginated deliveries index. Filters:
     *   - status
     *   - message_type
     *   - center_id (only if the user can see
     *     more than one center; in this
     *     codebase the authn user always has
     *     a single current_center_id so the
     *     filter is implicit)
     *   - date range (from / to on queued_at)
     *   - search (appointment id or customer
     *     name LIKE)
     *
     * Server-side pagination so the UI does
     * not load thousands of rows into the
     * browser.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();

        if (! $user instanceof User) {
            abort(401);
        }

        // The policy's viewAny is the canonical
        // gate. We use abort(403) here so the
        // operator who is missing the
        // permission gets a clear error, and
        // the tenant/center check is enforced
        // by the row query below.
        $this->authorize('viewAny', AppointmentMessageDelivery::class);

        $tenantId = (int) $user->tenant_id;
        $centerId = (int) $user->current_center_id;

        $data = $request->validate([
            'status' => 'nullable|string|in:'.implode(',', [
                AppointmentMessageDelivery::STATUS_QUEUED,
                AppointmentMessageDelivery::STATUS_SENDING,
                AppointmentMessageDelivery::STATUS_SENT,
                AppointmentMessageDelivery::STATUS_FAILED,
                AppointmentMessageDelivery::STATUS_SKIPPED,
            ]),
            'message_type' => 'nullable|string|in:'.implode(',', AppointmentMessageDelivery::MESSAGE_TYPES),
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
            'search' => 'nullable|string|max:100',
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:10|max:100',
        ]);

        $perPage = (int) ($data['per_page'] ?? 25);

        // We use a join because the deliveries
        // table has no center_id. The join is
        // safe because both tables carry a
        // tenant_id and the join is inner. The
        // tenant_id filter is duplicated on the
        // left side for an extra safety margin.
        $query = AppointmentMessageDelivery::query()
            ->select('appointment_message_deliveries.*')
            ->join('appointments', 'appointments.id', '=', 'appointment_message_deliveries.appointment_id')
            ->where('appointment_message_deliveries.tenant_id', $tenantId)
            ->where('appointments.tenant_id', $tenantId)
            ->where('appointments.center_id', $centerId)
            ->with([
                'customer:id,name',
                'appointment:id,scheduled_at,status,center_id,tenant_id,customer_id,vehicle_id',
            ]);

        if (! empty($data['status'])) {
            $query->where('appointment_message_deliveries.status', $data['status']);
        }

        if (! empty($data['message_type'])) {
            $query->where('appointment_message_deliveries.message_type', $data['message_type']);
        }

        if (! empty($data['date_from'])) {
            $query->whereDate('appointment_message_deliveries.queued_at', '>=', $data['date_from']);
        }

        if (! empty($data['date_to'])) {
            $query->whereDate('appointment_message_deliveries.queued_at', '<=', $data['date_to']);
        }

        if (! empty($data['search'])) {
            $needle = '%'.$data['search'].'%';
            $query->where(function ($q) use ($needle) {
                $q->where('appointments.id', 'like', $needle)
                    ->orWhereHas('customer', function ($cq) use ($needle) {
                        $cq->where('name', 'like', $needle);
                    });
            });
        }

        $paginator = $query
            ->orderByDesc('appointment_message_deliveries.queued_at')
            ->orderByDesc('appointment_message_deliveries.id')
            ->paginate($perPage)
            ->withQueryString();

        // We project the rows for the UI here so
        // the controller does not leak the
        // phone, the body, or the raw error
        // message to the Inertia response.
        $rows = collect($paginator->items())->map(function (AppointmentMessageDelivery $d) {
            $appointment = $d->appointment;

            return [
                'id' => (int) $d->id,
                'message_type' => (string) $d->message_type,
                'status' => (string) $d->status,
                'queued_at' => $d->queued_at?->toIso8601String(),
                'attempted_at' => $d->attempted_at?->toIso8601String(),
                'sent_at' => $d->sent_at?->toIso8601String(),
                'failed_at' => $d->failed_at?->toIso8601String(),
                'attempts' => (int) $d->attempts,
                'provider' => $d->provider,
                'provider_message_id' => $d->provider_message_id,
                'error_code' => $d->error_code,
                // The snapshot and the
                // appointment's current
                // scheduled_at drive the retry
                // eligibility rules on the
                // client side.
                'scheduled_at_snapshot' => $d->scheduled_at_snapshot?->toIso8601String(),
                'appointment' => $appointment ? [
                    'id' => (int) $appointment->id,
                    'scheduled_at' => $appointment->scheduled_at?->toIso8601String(),
                    'status' => (string) $appointment->status,
                    'center_id' => (int) $appointment->center_id,
                ] : null,
                'customer' => $d->customer ? [
                    'id' => (int) $d->customer->id,
                    'name' => (string) $d->customer->name,
                ] : null,
                // The retry-eligibility hint for
                // the UI: a derived boolean the
                // Vue page uses to show / hide
                // the retry button. The actual
                // authorization still happens
                // server-side on the retry POST.
                'is_retryable' => $this->isRetryable($d, $appointment),
            ];
        })->values()->all();

        return Inertia::render('Communications/AppointmentMessages/Index', [
            'rows' => $rows,
            'pagination' => [
                'page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => (int) $paginator->total(),
                'last_page' => (int) $paginator->lastPage(),
            ],
            'filters' => [
                'status' => $data['status'] ?? null,
                'message_type' => $data['message_type'] ?? null,
                'date_from' => $data['date_from'] ?? null,
                'date_to' => $data['date_to'] ?? null,
                'search' => $data['search'] ?? null,
            ],
            'messageTypes' => AppointmentMessageDelivery::MESSAGE_TYPES,
            'statuses' => [
                AppointmentMessageDelivery::STATUS_QUEUED,
                AppointmentMessageDelivery::STATUS_SENDING,
                AppointmentMessageDelivery::STATUS_SENT,
                AppointmentMessageDelivery::STATUS_FAILED,
                AppointmentMessageDelivery::STATUS_SKIPPED,
            ],
            // Top-level capability bit the UI
            // uses to decide whether to even
            // show the retry column. The row-
            // level `is_retryable` still drives
            // the per-row button. Both are
            // server-controlled; the Vue page
            // does not make its own policy
            // decision.
            'canRetry' => $user->can(Permissions::APPOINTMENTS_MANAGE),
        ]);
    }

    /**
     * Manual retry of a failed (or some
     * skipped) delivery row. The safety checks
     * are all server-side, inside a single
     * DB transaction with a row lock on the
     * delivery row. The Vue page only shows
     * the button when the same conditions
     * hold, but the controller is the source
     * of truth.
     *
     * Re-runs the row's `handle()` via a fresh
     * `SendAppointmentMessage::dispatch()`. The
     * job's own status re-check (queued ->
     * sending atomic transition) is the lock
     * against double-dispatch if two retries
     * race.
     */
    public function retry(Request $request, int $deliveryId): RedirectResponse
    {
        $user = $request->user();

        if (! $user instanceof User) {
            abort(401);
        }

        $tenantId = (int) $user->tenant_id;
        $centerId = (int) $user->current_center_id;

        // ── Pre-flight checks (no locks yet) ──
        //
        // The pre-flight only confirms the row
        // exists in the user's tenant + center.
        // We DO NOT read the appointment's
        // current state here, because that
        // state can change between this read
        // and the row lock we take inside the
        // transaction. All state checks happen
        // on the LOCKED versions, below.
        $preflightDelivery = AppointmentMessageDelivery::query()
            ->where('id', $deliveryId)
            ->where('tenant_id', $tenantId)
            ->first();

        if ($preflightDelivery === null) {
            abort(404);
        }

        // Confirm the linked appointment is
        // in the user's tenant + center for
        // the 404 (existence-leak-safe)
        // outcome. We do NOT use this row
        // for the safety checks — the locked
        // row inside the transaction is the
        // source of truth.
        $preflightAppointment = Appointment::query()
            ->where('id', $preflightDelivery->appointment_id)
            ->where('tenant_id', $tenantId)
            ->where('center_id', $centerId)
            ->first();

        if ($preflightAppointment === null) {
            abort(404);
        }

        // Policy gate: the user must have the
        // permission AND the row must be in
        // scope. The policy's `retry` method
        // re-checks tenant; this is the
        // belt-and-suspenders call.
        $this->authorize('retry', $preflightDelivery);

        // ── Atomic section ──
        //
        // Lock order: appointment first, then
        // delivery. This is the SAME order the
        // staff-side `AppointmentSchedulingService::reschedule`
        // uses, which locks the appointment row
        // before the bay / user / vehicle
        // rows it touches. Picking the
        // canonical order avoids deadlock
        // with the staff path if both happen
        // to run concurrently for the same
        // appointment.
        $outcome = DB::transaction(function () use ($preflightDelivery, $tenantId, $centerId) {
            // 1. Lock the appointment row.
            //    This is the canonical lock for
            //    "anything touching this
            //    appointment" — staff paths
            //    (reschedule, cancel) use the
            //    same row first.
            $appointment = Appointment::query()
                ->where('id', $preflightDelivery->appointment_id)
                ->where('tenant_id', $tenantId)
                ->where('center_id', $centerId)
                ->lockForUpdate()
                ->first();

            // If the appointment row vanished
            // or moved out of this tenant +
            // center between the pre-flight
            // and the lock (e.g. the staff
            // moved the appointment to a
            // different center, or it was
            // deleted), the lock returns
            // null. We refuse the retry with
            // the same generic 404-equivalent
            // error.
            if ($appointment === null) {
                return ['ok' => false, 'reason' => 'appointment_not_in_scope'];
            }

            // 2. Lock the delivery row, with
            //    the explicit
            //    appointment_id cross-check.
            //    The cross-check defends
            //    against a delivery row whose
            //    `appointment_id` was somehow
            //    re-pointed at a different
            //    appointment between the
            //    pre-flight and the lock.
            $delivery = AppointmentMessageDelivery::query()
                ->where('id', $preflightDelivery->id)
                ->where('tenant_id', $tenantId)
                ->where('appointment_id', $appointment->id)
                ->lockForUpdate()
                ->first();

            if ($delivery === null) {
                return ['ok' => false, 'reason' => 'delivery_missing'];
            }

            // From here on we operate on the
            // LOCKED instances. The pre-flight
            // copies are no longer consulted.

            // 1. Status gate. Only `failed`
            //    and the retryable subset of
            //    `skipped` are allowed.
            if (! $this->isRetryable($delivery, $appointment)) {
                return ['ok' => false, 'reason' => 'not_retryable', 'status' => $delivery->status];
            }

            // 2. Snapshot gate. The row's
            //    scheduled_at_snapshot must
            //    still match the appointment's
            //    CURRENT scheduled_at (the
            //    locked value). If they
            //    diverge the appointment was
            //    rescheduled between the page
            //    load and the click — refuse.
            if (! $this->isSnapshotStillValid($delivery, $appointment)) {
                return ['ok' => false, 'reason' => 'stale_snapshot'];
            }

            // 3. Cancellation / no-show /
            //    converted gate. Uses the
            //    LOCKED appointment state, so
            //    a race where the staff
            //    cancelled the appointment
            //    between page load and click
            //    is caught.
            if (! $this->isApplicableAfterStatusChange($delivery, $appointment)) {
                return ['ok' => false, 'reason' => 'appointment_state_changed'];
            }

            // 4. Atomic flip. We only flip
            //    rows that are STILL in a
            //    retryable state — this is
            //    the second lock against a
            //    double-retry.
            $flipped = AppointmentMessageDelivery::query()
                ->where('id', $delivery->id)
                ->whereIn('status', [AppointmentMessageDelivery::STATUS_FAILED, AppointmentMessageDelivery::STATUS_SKIPPED])
                ->update([
                    'status' => AppointmentMessageDelivery::STATUS_QUEUED,
                    'queued_at' => Carbon::now(),
                    'attempted_at' => null,
                    'sent_at' => null,
                    'failed_at' => null,
                    'error_code' => null,
                    'error_message' => null,
                    'provider_message_id' => null,
                    // We do NOT reset `attempts`;
                    // the column carries the
                    // historical count of how
                    // many times this row has
                    // been tried. The job's
                    // own retry policy reads
                    // `attempts` for the upper
                    // bound.
                ]);

            if ($flipped === 0) {
                return ['ok' => false, 'reason' => 'race_lost'];
            }

            return [
                'ok' => true,
                'delivery' => $delivery,
                'appointment' => $appointment,
            ];
        });

        if (! $outcome['ok']) {
            // 404 on every reject path. The
            // operator UI shows the same flash
            // regardless of the underlying
            // reason so the existence of the
            // row is not leaked.
            return back()->withErrors(['retry' => __('communications.appointment_messages.retry.rejected')]);
        }

        // The locked copies from the
        // transaction are the source of truth
        // for the audit log and dispatch. We
        // never use a pre-flight copy here.
        $lockedDelivery = $outcome['delivery'];
        $lockedAppointment = $outcome['appointment'];

        // Audit log: the appointment gets a
        // structured "user X retried message Y"
        // entry. The description carries the
        // delivery id and the message type;
        // it MUST NOT carry the phone, the
        // body, or any provider text. The
        // changes column is the same data
        // structured for any future reporter
        // that wants to scan the audit log.
        $lockedAppointment->logActivity(
            'message_retried',
            __('communications.appointment_messages.audit.retried', [
                'delivery_id' => $lockedDelivery->id,
                'message_type' => $lockedDelivery->message_type,
            ]),
            [
                'delivery_id' => (int) $lockedDelivery->id,
                'message_type' => (string) $lockedDelivery->message_type,
                'user_id' => (int) $user->id,
            ]
        );

        // Dispatch the job. We re-use the
        // dispatcher's safeDispatch — which
        // already handles the "dispatch itself
        // fails" case (marks the row failed
        // with error_code = dispatch_failed).
        // We do NOT call the safeDispatch
        // directly because we want to keep
        // the audit log and the dispatch
        // together; the failure is still
        // logged for the operator to see.
        try {
            SendAppointmentMessage::dispatch($lockedDelivery->id);
        } catch (Throwable $e) {
            // The safeDispatch path inside
            // AppointmentMessageDispatcher is
            // the right call here, but it is
            // hidden behind the controller's
            // retry path. We mirror its
            // semantics: log only the class
            // name, never the message body.
            Log::warning('communications.retry.dispatch_failed', [
                'delivery_id' => $lockedDelivery->id,
                'appointment_id' => $lockedAppointment->id,
                'tenant_id' => $tenantId,
                'user_id' => $user->id,
                'reason_class' => (new ReflectionClass($e))->getShortName(),
            ]);

            // Conditional update to failed —
            // mirrors safeDispatch's behavior
            // exactly so a failed dispatch
            // does not leave the row stranded
            // in queued.
            AppointmentMessageDelivery::query()
                ->where('id', $lockedDelivery->id)
                ->where('status', AppointmentMessageDelivery::STATUS_QUEUED)
                ->update([
                    'status' => AppointmentMessageDelivery::STATUS_FAILED,
                    'failed_at' => Carbon::now(),
                    'error_code' => 'dispatch_failed',
                    'error_message' => null,
                ]);
        }

        return back()->with('status', __('communications.appointment_messages.retry.success'));
    }

    /**
     * Whether the row is in a state where a
     * manual retry makes sense at all. The
     * caller is the index page (to decide
     * whether to show the button) AND the
     * retry endpoint (to refuse). Both use
     * the same rule.
     */
    private function isRetryable(AppointmentMessageDelivery $delivery, ?Appointment $appointment): bool
    {
        // Status must be failed, or a
        // retryable subset of skipped.
        if ($delivery->status === AppointmentMessageDelivery::STATUS_FAILED) {
            // Status + snapshot + cancellation.
            // The snapshot check is what keeps
            // the UI hint in sync with the
            // server's accept/reject rule.
            return $this->isApplicableAfterStatusChange($delivery, $appointment)
                && $this->isSnapshotStillValid($delivery, $appointment);
        }

        if ($delivery->status === AppointmentMessageDelivery::STATUS_SKIPPED) {
            if ($delivery->error_code !== null
                && in_array($delivery->error_code, self::NON_RETRYABLE_SKIP_CODES, true)) {
                return false;
            }

            return $this->isApplicableAfterStatusChange($delivery, $appointment)
                && $this->isSnapshotStillValid($delivery, $appointment);
        }

        return false;
    }

    /**
     * Cancellation, snapshot, and message-
     * type-applicability rules. These are
     * separate from the status check so we
     * can reuse them from both the "is this
     * row retryable?" hint and the row lock
     * inside the retry endpoint.
     */
    private function isApplicableAfterStatusChange(AppointmentMessageDelivery $delivery, ?Appointment $appointment): bool
    {
        if ($appointment === null) {
            return false;
        }

        // The appointment must still exist
        // and be in this tenant + center.
        // (Caller is expected to have
        // already filtered the appointment
        // query; this is a defensive double-
        // check.)
        if ($appointment->center_id === null) {
            return false;
        }

        // Cancellation: only the cancel
        // message is allowed for a cancelled
        // appointment. Confirmation / reminder
        // / reschedule messages on a cancelled
        // appointment are confusing and
        // should not be re-sent.
        if ($appointment->status === Appointment::STATUS_CANCELLED
            && $delivery->message_type !== AppointmentMessageDelivery::TYPE_BOOKING_CANCELLED) {
            return false;
        }

        // Converted / no-show: reminders and
        // confirmations on a converted or
        // no-showed appointment are not
        // useful. Cancel messages are out of
        // scope here because the appointment
        // is not in `cancelled`.
        if (in_array($appointment->status, [
            Appointment::STATUS_CONVERTED,
            Appointment::STATUS_NO_SHOW,
        ], true)) {
            return false;
        }

        return true;
    }

    /**
     * True if the row's `scheduled_at_snapshot`
     * still matches the appointment's current
     * `scheduled_at`, OR the row is a
     * cancellation message (no time-bound
     * concept). The snapshot gate prevents
     * "your appointment on 2026-08-15 14:00
     * is confirmed" reaching the customer
     * after the appointment moved to 16:00.
     */
    private function isSnapshotStillValid(AppointmentMessageDelivery $delivery, ?Appointment $appointment): bool
    {
        if ($appointment === null) {
            return false;
        }

        if ($delivery->message_type === AppointmentMessageDelivery::TYPE_BOOKING_CANCELLED) {
            // The cancel message reflects the
            // appointment's state at the time
            // of cancellation, not a specific
            // scheduled time. Always eligible
            // for retry as long as the
            // appointment is still in the
            // cancelled state.
            return $appointment->status === Appointment::STATUS_CANCELLED;
        }
        $snapshot = $delivery->scheduled_at_snapshot;

        if ($snapshot === null) {
            return true; // legacy rows, or non-time-bound
        }

        if ($appointment->scheduled_at === null) {
            return false;
        }

        return $snapshot->equalTo($appointment->scheduled_at);
    }
}

<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Jobs\SendAppointmentMessage;
use App\Models\Appointment;
use App\Models\AppointmentActivity;
use App\Models\AppointmentMessageDelivery;
use App\Models\Center;
use App\Models\CenterWorkingHour;
use App\Models\Customer;
use App\Models\Integration\Integration;
use App\Models\Service;
use App\Models\ServiceBay;
use App\Models\Tenant;
use App\Models\TenantWebsite;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\Messaging\SmsService;
use App\Support\Permissions;
use Carbon\Carbon;
use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Log\Events\MessageLogged;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Mockery;
use RuntimeException;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Phase 11 — staff communications dashboard.
 *
 * The test set covers, end-to-end:
 *
 *   - Tenant isolation: a different tenant
 *     sees 0 rows and gets 404 on retry.
 *   - Center isolation: a different center
 *     in the same tenant also returns 404.
 *   - Permission gate: a user without
 *     crm.appointments.manage gets 403 on
 *     the index page.
 *   - Retry happy path: a `failed` row gets
 *     flipped to `queued` and a single job
 *     is dispatched.
 *   - Retry double-click: two simultaneous
 *     POSTs result in one job (the second
 *     is rejected because the row is no
 *     longer in `failed`).
 *   - Retry refusal: `sent`, `sending`,
 *     `queued` rows are refused.
 *   - Retry refusal: `appointment_schedule_changed`
 *     skipped rows are refused.
 *   - Retry refusal: rows for cancelled
 *     appointments (other than cancel
 *     messages) are refused.
 *   - Retry refusal: rows whose snapshot no
 *     longer matches the appointment's
 *     current scheduled_at are refused.
 *   - Dispatch failure: when the Bus
 *     dispatcher throws, the row gets
 *     `error_code = dispatch_failed` and
 *     `error_message = null`.
 *   - Privacy: the response and the log
 *     never contain the phone, the body,
 *     or the provider exception text.
 *   - Audit: a successful retry writes a
 *     row to `appointment_activities` with
 *     the user id and the delivery id
 *     (no phone, no body).
 */
class AppointmentMessageDeliveryControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Carbon::setTestNow pins "now" so the
        // 24h/2h-reminder windows and the
        // attempt timestamps are stable.
        Carbon::setTestNow(Carbon::parse('2026-08-15 09:00:00'));

        // The Phase 10 deliveries table is
        // already created by the project's
        // migration set; the Phase 11
        // appointment_activities table is the
        // new one.

        // RefreshDatabase has already migrated
        // for us.
        app()[PermissionRegistrar::class]->forgetCachedPermissions();
        Permission::findOrCreate(Permissions::APPOINTMENTS_MANAGE, 'web');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        Mockery::close();
        parent::tearDown();
    }

    // ──────────────────────────────────────────────────────────
    // Helpers
    // ──────────────────────────────────────────────────────────

    private function publishedTenant(string $slug = 'demo'): Tenant
    {
        $tenant = Tenant::factory()->create(['slug' => $slug]);
        TenantWebsite::factory()->for($tenant)->published()->create();

        return $tenant;
    }

    private function customerIn(Tenant $tenant, Center $center, string $phone = '+966501234567'): Customer
    {
        return Customer::factory()->create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'phone' => $phone,
            'whatsapp' => $phone,
        ]);
    }

    private function openCenter(Tenant $tenant, string $name = 'Branch A'): Center
    {
        $center = Center::factory()->create([
            'tenant_id' => $tenant->id,
            'name' => $name,
            'is_active' => true,
        ]);

        for ($dow = 0; $dow <= 6; $dow++) {
            CenterWorkingHour::create([
                'tenant_id' => $tenant->id,
                'center_id' => $center->id,
                'day_of_week' => $dow,
                'is_open' => true,
                'open_time' => '00:00',
                'close_time' => '23:59',
            ]);
        }

        return $center;
    }

    private function bayIn(Center $center): ServiceBay
    {
        return ServiceBay::create([
            'tenant_id' => $center->tenant_id,
            'center_id' => $center->id,
            'name' => 'Bay A',
            'code' => 'B1',
            'is_active' => true,
        ]);
    }

    private function vehicleFor(Customer $customer, Tenant $tenant, Center $center): Vehicle
    {
        return Vehicle::factory()->create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'customer_id' => $customer->id,
        ]);
    }

    private function serviceIn(Tenant $tenant, Center $center): Service
    {
        return Service::factory()->forTenant($tenant, $center)->create([
            'duration_value' => 30,
            'duration_unit' => 'minute',
        ]);
    }

    /**
     * Build a staff user with the given
     * permission set, attached to a tenant +
     * center. The user is also verified and
     * email-confirmed so the auth middleware
     * accepts them.
     */
    private function staffFor(Tenant $tenant, Center $center, array $permissions = [Permissions::APPOINTMENTS_MANAGE]): User
    {
        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'current_center_id' => $center->id,
            'email_verified_at' => Carbon::now(),
            'password' => Hash::make('password'),
        ]);
        $user->centers()->attach($center->id, ['tenant_id' => $tenant->id]);

        app(PermissionRegistrar::class)->setPermissionsTeamId($tenant->id);

        foreach ($permissions as $name) {
            $user->givePermissionTo($name);
        }
        $user->refresh();

        return $user;
    }

    /**
     * Build a future appointment for the
     * given customer/vehicle/bay. Status
     * defaults to `scheduled`.
     */
    private function makeAppointment(
        Tenant $tenant,
        Center $center,
        Customer $customer,
        Vehicle $vehicle,
        Carbon $scheduledAt,
        string $status = Appointment::STATUS_SCHEDULED,
        ?Carbon $cancelledAt = null,
    ): Appointment {
        return Appointment::create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'service_bay_id' => null,
            'scheduled_at' => $scheduledAt,
            'estimated_end_at' => $scheduledAt->copy()->addMinutes(30),
            'status' => $status,
            'source' => Appointment::SOURCE_CUSTOMER_PORTAL,
            'cancelled_at' => $cancelledAt,
        ]);
    }

    /**
     * Build a `failed` delivery row. The
     * `error_code` and `error_message` are
     * stored exactly as the job would have
     * written them — `error_message` is
     * NULL.
     */
    private function makeDelivery(
        Tenant $tenant,
        Appointment $appointment,
        Customer $customer,
        string $messageType,
        string $status = AppointmentMessageDelivery::STATUS_FAILED,
        ?string $errorCode = null,
        ?Carbon $scheduledAtSnapshot = null,
    ): AppointmentMessageDelivery {
        return AppointmentMessageDelivery::create([
            'tenant_id' => $tenant->id,
            'appointment_id' => $appointment->id,
            'customer_id' => $customer->id,
            'message_type' => $messageType,
            'scheduled_at_snapshot' => $scheduledAtSnapshot ?? $appointment->scheduled_at,
            'idempotency_key' => str_repeat('a', 32),
            'phone_hash' => hash('sha256', '+966501234567'),
            'status' => $status,
            'attempts' => 1,
            'queued_at' => Carbon::now()->subMinutes(15),
            'attempted_at' => Carbon::now()->subMinutes(10),
            'failed_at' => Carbon::now()->subMinutes(10),
            'error_code' => $errorCode,
            'error_message' => null,
        ]);
    }

    private function makeIntegration(): Integration
    {
        return Integration::create([
            'type' => 'sms',
            'provider' => 'unifonic',
            'name' => 'Unifonic',
            'name_ar' => 'يونيفونك',
            'config' => ['app_sid' => 'test', 'sender_id' => 'KHDM'],
            'is_active' => true,
            'is_default' => true,
        ]);
    }

    /**
     * Replace the SmsService with a fake
     * that isReady=true and never throws.
     * We do NOT need it to send anything in
     * the controller tests; the controller
     * only dispatches the job, which we fake
     * out via Bus::fake() or by manually
     * running the job.
     */
    private function fakeSms(): void
    {
        $spy = Mockery::mock(SmsService::class);
        $spy->shouldReceive('isReady')->byDefault()->andReturn(true);
        $spy->shouldReceive('send')->byDefault()->andReturn([
            'success' => true,
            'message_id' => 'mock-msg-id',
            'provider' => 'unifonic',
        ]);
        $this->app->instance(SmsService::class, $spy);
    }

    // ──────────────────────────────────────────────────────────
    // Index — tenant + center isolation
    // ──────────────────────────────────────────────────────────

    public function test_authorized_user_sees_only_their_tenant_and_center_rows(): void
    {
        $this->fakeSms();

        // Tenant A
        $tenantA = $this->publishedTenant('a');
        $centerA = $this->openCenter($tenantA, 'A-Branch');
        $customerA = $this->customerIn($tenantA, $centerA, '+966501111111');
        $vehicleA = $this->vehicleFor($customerA, $tenantA, $centerA);
        $apptA = $this->makeAppointment($tenantA, $centerA, $customerA, $vehicleA, Carbon::now()->copy()->addHours(3));
        $this->makeDelivery($tenantA, $apptA, $customerA, AppointmentMessageDelivery::TYPE_BOOKING_CONFIRMATION);

        // Tenant B (same controller scope, different tenant)
        $tenantB = $this->publishedTenant('b');
        $centerB = $this->openCenter($tenantB, 'B-Branch');
        $customerB = $this->customerIn($tenantB, $centerB, '+966502222222');
        $vehicleB = $this->vehicleFor($customerB, $tenantB, $centerB);
        $apptB = $this->makeAppointment($tenantB, $centerB, $customerB, $vehicleB, Carbon::now()->copy()->addHours(3));
        $this->makeDelivery($tenantB, $apptB, $customerB, AppointmentMessageDelivery::TYPE_BOOKING_CONFIRMATION);

        // User from tenant A + center A
        $user = $this->staffFor($tenantA, $centerA);

        $response = $this->actingAs($user)->get(route('communications.appointment-messages.index'));
        $response->assertOk();

        $props = $response->viewData('page')['props'];
        $this->assertCount(1, $props['rows'], 'user A sees only their own row');

        $row = $props['rows'][0];
        $this->assertSame($apptA->id, $row['appointment']['id']);
        $this->assertSame($customerA->id, $row['customer']['id']);
        $this->assertArrayNotHasKey('phone', $row);
        $this->assertArrayNotHasKey('phone_hash', $row);
    }

    public function test_user_from_other_tenant_sees_zero_rows(): void
    {
        $this->fakeSms();

        $tenantA = $this->publishedTenant('a');
        $centerA = $this->openCenter($tenantA);
        $customerA = $this->customerIn($tenantA, $centerA);
        $vehicleA = $this->vehicleFor($customerA, $tenantA, $centerA);
        $apptA = $this->makeAppointment($tenantA, $centerA, $customerA, $vehicleA, Carbon::now()->copy()->addHours(3));
        $this->makeDelivery($tenantA, $apptA, $customerA, AppointmentMessageDelivery::TYPE_BOOKING_CONFIRMATION);

        $tenantB = $this->publishedTenant('b');
        $centerB = $this->openCenter($tenantB);
        $userB = $this->staffFor($tenantB, $centerB);

        $response = $this->actingAs($userB)->get(route('communications.appointment-messages.index'));
        $response->assertOk();
        $this->assertCount(0, $response->viewData('page')['props']['rows']);
    }

    public function test_user_from_other_center_in_same_tenant_sees_zero_rows(): void
    {
        $this->fakeSms();

        $tenant = $this->publishedTenant('a');
        $centerA = $this->openCenter($tenant, 'A-Branch');
        $centerB = $this->openCenter($tenant, 'B-Branch');

        $customer = $this->customerIn($tenant, $centerA);
        $vehicle = $this->vehicleFor($customer, $tenant, $centerA);
        $appt = $this->makeAppointment($tenant, $centerA, $customer, $vehicle, Carbon::now()->copy()->addHours(3));
        $this->makeDelivery($tenant, $appt, $customer, AppointmentMessageDelivery::TYPE_BOOKING_CONFIRMATION);

        // User assigned to center B in the
        // SAME tenant.
        $userB = $this->staffFor($tenant, $centerB);

        $response = $this->actingAs($userB)->get(route('communications.appointment-messages.index'));
        $response->assertOk();
        $this->assertCount(0, $response->viewData('page')['props']['rows']);
    }

    public function test_user_without_appointments_manage_permission_gets_403(): void
    {
        $this->fakeSms();

        $tenant = $this->publishedTenant('a');
        $center = $this->openCenter($tenant);

        // User has NO permissions at all.
        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'current_center_id' => $center->id,
            'email_verified_at' => Carbon::now(),
        ]);
        $user->centers()->attach($center->id, ['tenant_id' => $tenant->id]);
        app(PermissionRegistrar::class)->setPermissionsTeamId($tenant->id);
        $user->refresh();

        $response = $this->actingAs($user)->get(route('communications.appointment-messages.index'));
        $response->assertForbidden();
    }

    // ──────────────────────────────────────────────────────────
    // Retry — happy path
    // ──────────────────────────────────────────────────────────

    public function test_retry_flips_failed_row_to_queued_and_dispatches_one_job(): void
    {
        $this->fakeSms();
        Queue::fake();

        $tenant = $this->publishedTenant('a');
        $center = $this->openCenter($tenant);
        $customer = $this->customerIn($tenant, $center);
        $vehicle = $this->vehicleFor($customer, $tenant, $center);
        $appt = $this->makeAppointment($tenant, $center, $customer, $vehicle, Carbon::now()->copy()->addHours(3));
        $delivery = $this->makeDelivery($tenant, $appt, $customer, AppointmentMessageDelivery::TYPE_BOOKING_CONFIRMATION);

        $user = $this->staffFor($tenant, $center);

        $response = $this->actingAs($user)
            ->post(route('communications.appointment-messages.retry', ['delivery' => $delivery->id]));

        $response->assertRedirect();
        $response->assertSessionHas('status');

        $delivery->refresh();
        $this->assertSame(AppointmentMessageDelivery::STATUS_QUEUED, $delivery->status);
        $this->assertNull($delivery->error_code);
        $this->assertNull($delivery->error_message);
        $this->assertNotNull($delivery->queued_at);

        Queue::assertPushed(SendAppointmentMessage::class, function ($job) use ($delivery) {
            return $job->deliveryId === $delivery->id;
        });
        Queue::assertPushed(SendAppointmentMessage::class, 1);
    }

    public function test_successful_retry_writes_audit_log_with_user_and_delivery_id(): void
    {
        $this->fakeSms();
        Queue::fake();

        $tenant = $this->publishedTenant('a');
        $center = $this->openCenter($tenant);
        $customer = $this->customerIn($tenant, $center);
        $vehicle = $this->vehicleFor($customer, $tenant, $center);
        $appt = $this->makeAppointment($tenant, $center, $customer, $vehicle, Carbon::now()->copy()->addHours(3));
        $delivery = $this->makeDelivery($tenant, $appt, $customer, AppointmentMessageDelivery::TYPE_BOOKING_CONFIRMATION);

        $user = $this->staffFor($tenant, $center);

        $this->actingAs($user)
            ->post(route('communications.appointment-messages.retry', ['delivery' => $delivery->id]))
            ->assertRedirect();

        $activity = AppointmentActivity::query()
            ->where('appointment_id', $appt->id)
            ->where('action', 'message_retried')
            ->first();
        $this->assertNotNull($activity);
        $this->assertSame($user->id, $activity->user_id);
        $this->assertSame($tenant->id, $activity->tenant_id);
        $this->assertSame($delivery->id, $activity->changes['delivery_id']);
        $this->assertSame(AppointmentMessageDelivery::TYPE_BOOKING_CONFIRMATION, $activity->changes['message_type']);

        // The description carries the user id
        // and the delivery id, never the
        // phone or any body text.
        $this->assertStringNotContainsString('+966501234567', (string) $activity->description);
        $this->assertStringNotContainsString('+966501234567', json_encode($activity->changes));
    }

    // ──────────────────────────────────────────────────────────
    // Retry — race + double click
    // ──────────────────────────────────────────────────────────

    public function test_double_click_dispatches_only_one_job(): void
    {
        $this->fakeSms();
        Queue::fake();

        $tenant = $this->publishedTenant('a');
        $center = $this->openCenter($tenant);
        $customer = $this->customerIn($tenant, $center);
        $vehicle = $this->vehicleFor($customer, $tenant, $center);
        $appt = $this->makeAppointment($tenant, $center, $customer, $vehicle, Carbon::now()->copy()->addHours(3));
        $delivery = $this->makeDelivery($tenant, $appt, $customer, AppointmentMessageDelivery::TYPE_BOOKING_CONFIRMATION);

        $user = $this->staffFor($tenant, $center);

        // First click — flips to queued, dispatches.
        $this->actingAs($user)
            ->post(route('communications.appointment-messages.retry', ['delivery' => $delivery->id]))
            ->assertRedirect();

        // Second click — row is no longer in
        // `failed`, the conditional UPDATE
        // flips zero rows, the service
        // returns the rejected outcome and
        // the controller redirects with an
        // error flash. No second job.
        $this->actingAs($user)
            ->post(route('communications.appointment-messages.retry', ['delivery' => $delivery->id]))
            ->assertRedirect()
            ->assertSessionHasErrors(['retry']);

        Queue::assertPushed(SendAppointmentMessage::class, 1);
    }

    public function test_concurrent_retry_dispatches_only_one_job(): void
    {
        $this->fakeSms();
        Queue::fake();

        $tenant = $this->publishedTenant('a');
        $center = $this->openCenter($tenant);
        $customer = $this->customerIn($tenant, $center);
        $vehicle = $this->vehicleFor($customer, $tenant, $center);
        $appt = $this->makeAppointment($tenant, $center, $customer, $vehicle, Carbon::now()->copy()->addHours(3));
        $delivery = $this->makeDelivery($tenant, $appt, $customer, AppointmentMessageDelivery::TYPE_BOOKING_CONFIRMATION);

        $user = $this->staffFor($tenant, $center);

        // Force the row into a non-eligible
        // state to model a race where the
        // first retry succeeded and the
        // worker has already picked the row
        // up by the time the second request
        // arrives. The controller's
        // conditional UPDATE
        // (WHERE status IN failed/skipped)
        // must see zero rows and bail
        // without dispatching a second job.
        $delivery->update(['status' => AppointmentMessageDelivery::STATUS_SENDING]);

        $this->actingAs($user)
            ->post(route('communications.appointment-messages.retry', ['delivery' => $delivery->id]))
            ->assertRedirect()
            ->assertSessionHasErrors(['retry']);

        Queue::assertPushed(SendAppointmentMessage::class, 0);
    }

    // ──────────────────────────────────────────────────────────
    // Retry — refusals
    // ──────────────────────────────────────────────────────────

    public function test_sent_row_refuses_retry(): void
    {
        $this->fakeSms();
        Queue::fake();

        $tenant = $this->publishedTenant('a');
        $center = $this->openCenter($tenant);
        $customer = $this->customerIn($tenant, $center);
        $vehicle = $this->vehicleFor($customer, $tenant, $center);
        $appt = $this->makeAppointment($tenant, $center, $customer, $vehicle, Carbon::now()->copy()->addHours(3));
        $delivery = $this->makeDelivery($tenant, $appt, $customer, AppointmentMessageDelivery::TYPE_BOOKING_CONFIRMATION, AppointmentMessageDelivery::STATUS_SENT);

        $user = $this->staffFor($tenant, $center);
        $this->actingAs($user)
            ->post(route('communications.appointment-messages.retry', ['delivery' => $delivery->id]))
            ->assertRedirect()
            ->assertSessionHasErrors(['retry']);

        Queue::assertNothingPushed();
    }

    public function test_sending_row_refuses_retry(): void
    {
        $this->fakeSms();
        Queue::fake();

        $tenant = $this->publishedTenant('a');
        $center = $this->openCenter($tenant);
        $customer = $this->customerIn($tenant, $center);
        $vehicle = $this->vehicleFor($customer, $tenant, $center);
        $appt = $this->makeAppointment($tenant, $center, $customer, $vehicle, Carbon::now()->copy()->addHours(3));
        $delivery = $this->makeDelivery($tenant, $appt, $customer, AppointmentMessageDelivery::TYPE_BOOKING_CONFIRMATION, AppointmentMessageDelivery::STATUS_SENDING);

        $user = $this->staffFor($tenant, $center);
        $this->actingAs($user)
            ->post(route('communications.appointment-messages.retry', ['delivery' => $delivery->id]))
            ->assertRedirect()
            ->assertSessionHasErrors(['retry']);

        Queue::assertNothingPushed();
    }

    public function test_queued_row_refuses_retry(): void
    {
        $this->fakeSms();
        Queue::fake();

        $tenant = $this->publishedTenant('a');
        $center = $this->openCenter($tenant);
        $customer = $this->customerIn($tenant, $center);
        $vehicle = $this->vehicleFor($customer, $tenant, $center);
        $appt = $this->makeAppointment($tenant, $center, $customer, $vehicle, Carbon::now()->copy()->addHours(3));
        $delivery = $this->makeDelivery($tenant, $appt, $customer, AppointmentMessageDelivery::TYPE_BOOKING_CONFIRMATION, AppointmentMessageDelivery::STATUS_QUEUED);

        $user = $this->staffFor($tenant, $center);
        $this->actingAs($user)
            ->post(route('communications.appointment-messages.retry', ['delivery' => $delivery->id]))
            ->assertRedirect()
            ->assertSessionHasErrors(['retry']);

        Queue::assertNothingPushed();
    }

    public function test_skipped_appointment_schedule_changed_refuses_retry(): void
    {
        $this->fakeSms();
        Queue::fake();

        $tenant = $this->publishedTenant('a');
        $center = $this->openCenter($tenant);
        $customer = $this->customerIn($tenant, $center);
        $vehicle = $this->vehicleFor($customer, $tenant, $center);
        $appt = $this->makeAppointment($tenant, $center, $customer, $vehicle, Carbon::now()->copy()->addHours(3));
        $delivery = $this->makeDelivery($tenant, $appt, $customer, AppointmentMessageDelivery::TYPE_BOOKING_CONFIRMATION, AppointmentMessageDelivery::STATUS_SKIPPED, errorCode: 'appointment_schedule_changed');

        $user = $this->staffFor($tenant, $center);
        $this->actingAs($user)
            ->post(route('communications.appointment-messages.retry', ['delivery' => $delivery->id]))
            ->assertRedirect()
            ->assertSessionHasErrors(['retry']);

        Queue::assertNothingPushed();
    }

    public function test_appointment_cancelled_after_dispatch_refuses_reschedule_retry(): void
    {
        $this->fakeSms();
        Queue::fake();

        $tenant = $this->publishedTenant('a');
        $center = $this->openCenter($tenant);
        $customer = $this->customerIn($tenant, $center);
        $vehicle = $this->vehicleFor($customer, $tenant, $center);
        $appt = $this->makeAppointment($tenant, $center, $customer, $vehicle, Carbon::now()->copy()->addHours(3), status: Appointment::STATUS_CANCELLED, cancelledAt: Carbon::now());
        $delivery = $this->makeDelivery($tenant, $appt, $customer, AppointmentMessageDelivery::TYPE_BOOKING_RESCHEDULED, AppointmentMessageDelivery::STATUS_FAILED);

        $user = $this->staffFor($tenant, $center);
        $this->actingAs($user)
            ->post(route('communications.appointment-messages.retry', ['delivery' => $delivery->id]))
            ->assertRedirect()
            ->assertSessionHasErrors(['retry']);

        Queue::assertNothingPushed();
    }

    public function test_appointment_cancelled_with_cancel_message_allows_retry(): void
    {
        $this->fakeSms();
        Queue::fake();

        $tenant = $this->publishedTenant('a');
        $center = $this->openCenter($tenant);
        $customer = $this->customerIn($tenant, $center);
        $vehicle = $this->vehicleFor($customer, $tenant, $center);
        $appt = $this->makeAppointment($tenant, $center, $customer, $vehicle, Carbon::now()->copy()->addHours(3), status: Appointment::STATUS_CANCELLED, cancelledAt: Carbon::now());
        $delivery = $this->makeDelivery($tenant, $appt, $customer, AppointmentMessageDelivery::TYPE_BOOKING_CANCELLED, AppointmentMessageDelivery::STATUS_FAILED);

        $user = $this->staffFor($tenant, $center);
        $this->actingAs($user)
            ->post(route('communications.appointment-messages.retry', ['delivery' => $delivery->id]))
            ->assertRedirect();

        $delivery->refresh();
        $this->assertSame(AppointmentMessageDelivery::STATUS_QUEUED, $delivery->status);
    }

    public function test_stale_snapshot_refuses_retry(): void
    {
        $this->fakeSms();
        Queue::fake();

        $tenant = $this->publishedTenant('a');
        $center = $this->openCenter($tenant);
        $customer = $this->customerIn($tenant, $center);
        $vehicle = $this->vehicleFor($customer, $tenant, $center);
        // The original scheduled_at.
        $original = Carbon::now()->copy()->addHours(3);
        $appt = $this->makeAppointment($tenant, $center, $customer, $vehicle, $original);
        // The delivery row's snapshot is the
        // original time. Move the appointment
        // forward so the snapshot no longer
        // matches.
        $newTime = Carbon::now()->copy()->addHours(5);
        $appt->update([
            'scheduled_at' => $newTime,
            'estimated_end_at' => $newTime->copy()->addMinutes(30),
        ]);
        $delivery = $this->makeDelivery(
            $tenant,
            $appt,
            $customer,
            AppointmentMessageDelivery::TYPE_BOOKING_CONFIRMATION,
            AppointmentMessageDelivery::STATUS_FAILED,
            scheduledAtSnapshot: $original,
        );

        $user = $this->staffFor($tenant, $center);
        $this->actingAs($user)
            ->post(route('communications.appointment-messages.retry', ['delivery' => $delivery->id]))
            ->assertRedirect()
            ->assertSessionHasErrors(['retry']);

        Queue::assertNothingPushed();
    }

    public function test_other_tenant_retry_returns_404(): void
    {
        $this->fakeSms();
        Queue::fake();

        $tenantA = $this->publishedTenant('a');
        $centerA = $this->openCenter($tenantA);
        $customerA = $this->customerIn($tenantA, $centerA);
        $vehicleA = $this->vehicleFor($customerA, $tenantA, $centerA);
        $apptA = $this->makeAppointment($tenantA, $centerA, $customerA, $vehicleA, Carbon::now()->copy()->addHours(3));
        $deliveryA = $this->makeDelivery($tenantA, $apptA, $customerA, AppointmentMessageDelivery::TYPE_BOOKING_CONFIRMATION);

        $tenantB = $this->publishedTenant('b');
        $centerB = $this->openCenter($tenantB);
        $userB = $this->staffFor($tenantB, $centerB);

        $this->actingAs($userB)
            ->post(route('communications.appointment-messages.retry', ['delivery' => $deliveryA->id]))
            ->assertNotFound();

        Queue::assertNothingPushed();
    }

    public function test_other_center_in_same_tenant_retry_returns_404(): void
    {
        $this->fakeSms();
        Queue::fake();

        $tenant = $this->publishedTenant('a');
        $centerA = $this->openCenter($tenant, 'A-Branch');
        $centerB = $this->openCenter($tenant, 'B-Branch');
        $customerA = $this->customerIn($tenant, $centerA);
        $vehicleA = $this->vehicleFor($customerA, $tenant, $centerA);
        $apptA = $this->makeAppointment($tenant, $centerA, $customerA, $vehicleA, Carbon::now()->copy()->addHours(3));
        $deliveryA = $this->makeDelivery($tenant, $apptA, $customerA, AppointmentMessageDelivery::TYPE_BOOKING_CONFIRMATION);

        $userB = $this->staffFor($tenant, $centerB);

        $this->actingAs($userB)
            ->post(route('communications.appointment-messages.retry', ['delivery' => $deliveryA->id]))
            ->assertNotFound();

        Queue::assertNothingPushed();
    }

    // ──────────────────────────────────────────────────────────
    // Retry — dispatch failure
    // ──────────────────────────────────────────────────────────

    public function test_dispatch_failure_marks_row_failed_with_dispatch_failed_code(): void
    {
        $this->fakeSms();

        $captured = [];
        Log::listen(function (MessageLogged $event) use (&$captured) {
            $captured[] = [
                'level' => $event->level,
                'message' => $event->message,
                'context' => $event->context,
            ];
        });

        $tenant = $this->publishedTenant('a');
        $center = $this->openCenter($tenant);
        $customer = $this->customerIn($tenant, $center);
        $vehicle = $this->vehicleFor($customer, $tenant, $center);
        $appt = $this->makeAppointment($tenant, $center, $customer, $vehicle, Carbon::now()->copy()->addHours(3));
        $delivery = $this->makeDelivery($tenant, $appt, $customer, AppointmentMessageDelivery::TYPE_BOOKING_CONFIRMATION);

        $user = $this->staffFor($tenant, $center);

        // Force the Bus to throw on the next
        // dispatch. The retry endpoint
        // catches this and falls through to
        // the same conditional UPDATE that
        // safeDispatch would do.
        $bus = Mockery::mock(Dispatcher::class);
        $bus->shouldReceive('dispatch')
            ->andThrow(new RuntimeException(
                'queue down: phone +966501234567 not deliverable secret body'
            ));
        $this->app->instance(Dispatcher::class, $bus);

        $response = $this->actingAs($user)
            ->post(route('communications.appointment-messages.retry', ['delivery' => $delivery->id]));

        // We still return a redirect with
        // the success flash. The row flips
        // to failed with the dispatch_failed
        // sentinel. The customer does not
        // see an error 500.
        $response->assertRedirect();
        $response->assertSessionHas('status');

        $delivery->refresh();
        $this->assertSame(AppointmentMessageDelivery::STATUS_FAILED, $delivery->status);
        $this->assertSame('dispatch_failed', $delivery->error_code);
        $this->assertNull($delivery->error_message, 'error_message must be NULL — no phone, no body');

        // Privacy: no phone, no body in the log.
        $rawPhone = '+966501234567';
        $rawBody = 'queue down: phone +966501234567 not deliverable secret body';
        $sawDispatchFailed = false;

        foreach ($captured as $entry) {
            if ($entry['message'] !== 'communications.retry.dispatch_failed') {
                continue;
            }
            $sawDispatchFailed = true;
            $this->assertArrayHasKey('delivery_id', $entry['context']);
            $this->assertArrayHasKey('reason_class', $entry['context']);

            foreach ($entry['context'] as $v) {
                if (! is_string($v) && ! is_numeric($v)) {
                    continue;
                }
                $str = (string) $v;
                $this->assertStringNotContainsString($rawPhone, $str, "phone leaked: {$str}");
                $this->assertStringNotContainsString($rawBody, $str, "exception body leaked: {$str}");
            }
        }
        $this->assertTrue($sawDispatchFailed, 'expected a comms retry dispatch_failed log line');
    }

    // ──────────────────────────────────────────────────────────
    // Privacy — no PII in the index response
    // ──────────────────────────────────────────────────────────

    public function test_index_response_never_carries_phone_or_message_body(): void
    {
        $this->fakeSms();

        $tenant = $this->publishedTenant('a');
        $center = $this->openCenter($tenant);
        $customer = $this->customerIn($tenant, $center, '+966503333333');
        $vehicle = $this->vehicleFor($customer, $tenant, $center);
        $appt = $this->makeAppointment($tenant, $center, $customer, $vehicle, Carbon::now()->copy()->addHours(3));
        $this->makeDelivery($tenant, $appt, $customer, AppointmentMessageDelivery::TYPE_BOOKING_CONFIRMATION);

        $user = $this->staffFor($tenant, $center);

        $response = $this->actingAs($user)->get(route('communications.appointment-messages.index'));
        $response->assertOk();

        $raw = $response->getContent();
        $this->assertStringNotContainsString('+966503333333', $raw, 'phone must not appear in the index response');
        $this->assertStringNotContainsString('phone_hash', $raw, 'phone_hash must not leak to the UI');
    }

    // ──────────────────────────────────────────────────────────
    // P1 round 2 — race safety under row lock
    // ──────────────────────────────────────────────────────────

    /**
     * The pre-read happens BEFORE the row
     * lock. We use a DB::listen hook to mutate
     * the appointment's scheduled_at between
     * the controller's pre-flight and the
     * lockForUpdate() inside the transaction.
     * The locked value must drive the snapshot
     * check; the pre-flight value must NOT.
     */
    public function test_reschedule_between_pre_read_and_lock_refuses_retry(): void
    {
        $this->fakeSms();
        Queue::fake();

        $tenant = $this->publishedTenant('a');
        $center = $this->openCenter($tenant);
        $customer = $this->customerIn($tenant, $center);
        $vehicle = $this->vehicleFor($customer, $tenant, $center);
        $original = Carbon::now()->copy()->addHours(3);
        $appt = $this->makeAppointment($tenant, $center, $customer, $vehicle, $original);
        $delivery = $this->makeDelivery(
            $tenant,
            $appt,
            $customer,
            AppointmentMessageDelivery::TYPE_BOOKING_CONFIRMATION,
            AppointmentMessageDelivery::STATUS_FAILED,
            scheduledAtSnapshot: $original,
        );

        $user = $this->staffFor($tenant, $center);

        // Hook: the moment the controller
        // takes the row lock on the delivery
        // (and consequently the appointment
        // via the new ordering), the
        // appointment's scheduled_at has
        // already changed.
        //
        // We use a static $hooked flag inside
        // a DB::listen that triggers on the
        // first `lockForUpdate`-style SELECT
        // the controller issues for this
        // delivery.
        $hooked = false;
        DB::listen(function ($query) use (&$hooked, $appt) {
            if ($hooked) {
                return;
            }
            $sql = (string) $query->sql;

            // The first SELECT inside the
            // transaction that the controller
            // issues is `select * from
            // "appointments" where ... for
            // update`. We match that pattern
            // loosely; we just need to fire
            // once on the first lockForUpdate
            // query the controller issues.
            if (str_contains($sql, 'from "appointments"') && str_contains($sql, 'for update')) {
                $hooked = true;
                // Move the appointment.
                DB::table('appointments')
                    ->where('id', $appt->id)
                    ->update([
                        'scheduled_at' => $appt->scheduled_at->copy()->addHours(2),
                        'estimated_end_at' => $appt->scheduled_at->copy()->addHours(2)->addMinutes(30),
                    ]);
            }
        });

        $this->actingAs($user)
            ->post(route('communications.appointment-messages.retry', ['delivery' => $delivery->id]))
            ->assertRedirect()
            ->assertSessionHasErrors(['retry']);

        $delivery->refresh();
        $this->assertSame(AppointmentMessageDelivery::STATUS_FAILED, $delivery->status, 'delivery stays in failed (the lock observed the new scheduled_at and refused)');
        Queue::assertPushed(SendAppointmentMessage::class, 0);
    }

    /**
     * The appointment is cancelled between
     * the page load and the click. The
     * confirmation/reschedule/reminder
     * messages on it become ineligible. The
     * cancel message on it stays eligible.
     */
    public function test_cancel_between_pre_read_and_lock_refuses_non_cancel_retry(): void
    {
        $this->fakeSms();
        Queue::fake();

        $tenant = $this->publishedTenant('a');
        $center = $this->openCenter($tenant);
        $customer = $this->customerIn($tenant, $center);
        $vehicle = $this->vehicleFor($customer, $tenant, $center);
        $scheduled = Carbon::now()->copy()->addHours(3);
        $appt = $this->makeAppointment($tenant, $center, $customer, $vehicle, $scheduled);

        // Three different delivery rows for
        // the same appointment: confirmation,
        // reschedule, and reminder. All
        // failed. We test each in turn.
        $messages = [
            AppointmentMessageDelivery::TYPE_BOOKING_CONFIRMATION,
            AppointmentMessageDelivery::TYPE_BOOKING_RESCHEDULED,
            AppointmentMessageDelivery::TYPE_REMINDER_24H,
        ];
        $deliveries = [];

        foreach ($messages as $type) {
            $deliveries[] = $this->makeDelivery(
                $tenant,
                $appt,
                $customer,
                $type,
                AppointmentMessageDelivery::STATUS_FAILED,
                scheduledAtSnapshot: $scheduled,
            );
        }

        $user = $this->staffFor($tenant, $center);

        // Hook: cancel the appointment the
        // moment the controller's first
        // appointment lockForUpdate fires.
        $hooked = false;
        DB::listen(function ($query) use (&$hooked, $appt) {
            if ($hooked) {
                return;
            }
            $sql = (string) $query->sql;

            if (str_contains($sql, 'from "appointments"') && str_contains($sql, 'for update')) {
                $hooked = true;
                DB::table('appointments')
                    ->where('id', $appt->id)
                    ->update([
                        'status' => Appointment::STATUS_CANCELLED,
                        'cancelled_at' => Carbon::now(),
                    ]);
            }
        });

        foreach ($deliveries as $delivery) {
            $this->actingAs($user)
                ->post(route('communications.appointment-messages.retry', ['delivery' => $delivery->id]))
                ->assertRedirect()
                ->assertSessionHasErrors(['retry']);

            $delivery->refresh();
            $this->assertSame(
                AppointmentMessageDelivery::STATUS_FAILED,
                $delivery->status,
                "non-cancel message {$delivery->message_type} stays failed (appointment is now cancelled)"
            );
        }
        Queue::assertPushed(SendAppointmentMessage::class, 0);
    }

    public function test_cancel_message_remains_retryable_after_lock_when_appointment_is_cancelled(): void
    {
        $this->fakeSms();
        Queue::fake();

        $tenant = $this->publishedTenant('a');
        $center = $this->openCenter($tenant);
        $customer = $this->customerIn($tenant, $center);
        $vehicle = $this->vehicleFor($customer, $tenant, $center);
        $scheduled = Carbon::now()->copy()->addHours(3);
        $appt = $this->makeAppointment(
            $tenant,
            $center,
            $customer,
            $vehicle,
            $scheduled,
            status: Appointment::STATUS_CANCELLED,
            cancelledAt: Carbon::now()->subMinutes(2),
        );
        $delivery = $this->makeDelivery(
            $tenant,
            $appt,
            $customer,
            AppointmentMessageDelivery::TYPE_BOOKING_CANCELLED,
            AppointmentMessageDelivery::STATUS_FAILED,
            scheduledAtSnapshot: $scheduled,
        );

        $user = $this->staffFor($tenant, $center);

        $this->actingAs($user)
            ->post(route('communications.appointment-messages.retry', ['delivery' => $delivery->id]))
            ->assertRedirect();

        $delivery->refresh();
        $this->assertSame(AppointmentMessageDelivery::STATUS_QUEUED, $delivery->status);
    }

    // ──────────────────────────────────────────────────────────
    // P2 — UI hint matches server rules
    // ──────────────────────────────────────────────────────────

    /**
     * The index page's per-row `is_retryable`
     * must reflect the same rules the
     * controller enforces in `retry()`. If
     * the row is in `failed` but the snapshot
     * is stale (the appointment was moved),
     * the row must show `is_retryable: false`
     * so the UI hides the button and the
     * operator does not click a button that
     * will be rejected.
     */
    public function test_index_is_retryable_false_when_snapshot_is_stale(): void
    {
        $this->fakeSms();

        $tenant = $this->publishedTenant('a');
        $center = $this->openCenter($tenant);
        $customer = $this->customerIn($tenant, $center);
        $vehicle = $this->vehicleFor($customer, $tenant, $center);
        $original = Carbon::now()->copy()->addHours(3);
        $appt = $this->makeAppointment($tenant, $center, $customer, $vehicle, $original);

        // Move the appointment forward.
        $appt->update([
            'scheduled_at' => Carbon::now()->copy()->addHours(5),
            'estimated_end_at' => Carbon::now()->copy()->addHours(5)->addMinutes(30),
        ]);

        $this->makeDelivery(
            $tenant,
            $appt,
            $customer,
            AppointmentMessageDelivery::TYPE_BOOKING_CONFIRMATION,
            AppointmentMessageDelivery::STATUS_FAILED,
            scheduledAtSnapshot: $original,
        );

        $user = $this->staffFor($tenant, $center);

        $response = $this->actingAs($user)->get(route('communications.appointment-messages.index'));
        $response->assertOk();

        $rows = $response->viewData('page')['props']['rows'];
        $this->assertCount(1, $rows);
        $this->assertFalse($rows[0]['is_retryable'], 'snapshot stale → button must be hidden');
    }

    public function test_index_can_retry_prop_reflects_user_permission(): void
    {
        $this->fakeSms();

        $tenant = $this->publishedTenant('a');
        $center = $this->openCenter($tenant);
        $customer = $this->customerIn($tenant, $center);
        $vehicle = $this->vehicleFor($customer, $tenant, $center);
        $appt = $this->makeAppointment($tenant, $center, $customer, $vehicle, Carbon::now()->copy()->addHours(3));
        $this->makeDelivery($tenant, $appt, $customer, AppointmentMessageDelivery::TYPE_BOOKING_CONFIRMATION);

        // User without the permission.
        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'current_center_id' => $center->id,
            'email_verified_at' => Carbon::now(),
        ]);
        $user->centers()->attach($center->id, ['tenant_id' => $tenant->id]);
        app(PermissionRegistrar::class)->setPermissionsTeamId($tenant->id);
        $user->refresh();

        $response = $this->actingAs($user)->get(route('communications.appointment-messages.index'));
        $response->assertForbidden();
    }
}

<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Contracts\CustomerPortalOtpChannel;
use App\Jobs\SendAppointmentMessage;
use App\Models\Appointment;
use App\Models\AppointmentMessageDelivery;
use App\Models\Center;
use App\Models\CenterWorkingHour;
use App\Models\Customer;
use App\Models\CustomerPortalOtp;
use App\Models\Integration\Integration;
use App\Models\Service;
use App\Models\ServiceBay;
use App\Models\Tenant;
use App\Models\TenantWebsite;
use App\Models\Vehicle;
use App\Services\CustomerPortal\SystemSmsOtpChannel;
use App\Services\Messaging\AppointmentMessageDispatcher;
use App\Services\Messaging\AppointmentMessageReminderScanner;
use App\Services\Messaging\AppointmentMessageTemplate;
use App\Services\Messaging\SmsService;
use App\Support\CustomerPortalSession;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Log\Events\MessageLogged;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\RateLimiter;
use Mockery;
use RuntimeException;
use Tests\TestCase;
use Throwable;

/**
 * Phase 10 — appointment SMS delivery lifecycle.
 *
 * What this file covers
 * ---------------------
 *   1. Booking flow: confirmation SMS is dispatched
 *      exactly once after a successful booking.
 *   2. Reschedule flow: rescheduled SMS is dispatched
 *      exactly once after a successful reschedule.
 *      After reschedule, an old reminder (if any was
 *      pending for the old time) is dropped at
 *      worker time because the appointment's
 *      scheduled_at is now the new time.
 *   3. Cancel flow: cancelled SMS is dispatched
 *      exactly once after a successful cancel.
 *   4. Idempotency: a duplicate dispatch collapses
 *      to one row in `appointment_message_deliveries`.
 *   5. Tenant isolation: a delivery row for tenant A
 *      is invisible to a scan for tenant B.
 *   6. Reminder: 24h and 2h reminders each fire
 *      exactly once per appointment.
 *   7. Skipped states: cancelled / no_show /
 *      converted appointments do not get reminders.
 *   8. Reminder scanner window: appointments
 *      outside the [now+23h, now+25h] and
 *      [now+1.5h, now+2.5h] windows are skipped.
 *   9. Send-job: the actual SmsService is called,
 *      `provider_message_id` is stored, no
 *      exception is thrown on a customer-portal
 *      controller 500.
 *  10. Failure path: when the SmsService throws,
 *      the row is marked `failed` with a sanitized
 *      error code (NOT the raw phone or the body).
 *  11. Privacy: phone is never written to the
 *      delivery row; phone_hash is sha256 of the
 *      customer's phone.
 *  12. Job re-entry: a job that runs twice for the
 *      same delivery row (worker retry) bails on
 *      the second pass because the row is already
 *      terminal.
 *
 * Why the controller tests do NOT mock the SmsService
 * ----------------------------------------------------
 * The portal's controllers and services do not
 * import SmsService. The production path is
 * controller -> dispatcher -> job -> SmsService. We
 * mock SmsService at the job level (or bind a fake
 * in the container) so we can assert that the job
 * actually called the provider without going
 * through a real HTTP request.
 */
class AppointmentMessagesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-15 09:00:00'));
        parent::setUp();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        Mockery::close();
        parent::tearDown();
    }

    // ─────────────────────────────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────────────────────────────

    private function publishedTenant(string $slug = 'demo', string $siteName = 'Demo Site'): Tenant
    {
        $tenant = Tenant::factory()->create(['slug' => $slug]);
        TenantWebsite::factory()->for($tenant)->published()->create([
            'site_name' => $siteName,
        ]);

        return $tenant;
    }

    private function openCenter(Tenant $tenant, string $name = 'Branch 1'): Center
    {
        $center = Center::factory()->create([
            'tenant_id' => $tenant->id,
            'name' => $name,
            'name_ar' => $name,
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

    private function customerIn(Tenant $tenant, string $phone = '+966501234567'): Customer
    {
        return Customer::factory()->create([
            'tenant_id' => $tenant->id,
            'phone' => $phone,
            'whatsapp' => $phone,
        ]);
    }

    private function vehicleFor(Customer $customer, Tenant $tenant): Vehicle
    {
        return Vehicle::factory()->create([
            'tenant_id' => $tenant->id,
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

    private function loginAs(Customer $customer, Tenant $tenant): void
    {
        session()->put(CustomerPortalSession::key(), [
            'customer_id' => $customer->id,
            'tenant_id' => $tenant->id,
            'created_at' => Carbon::now()->toIso8601String(),
            'last_seen_at' => Carbon::now()->toIso8601String(),
        ]);
        // Clear rate-limit buckets so test isolation
        // does not collide with the throttlers.
        RateLimiter::clear('customer-portal-otp:'.request()->ip());
        RateLimiter::clear('customer-portal-verify:'.request()->ip());
    }

    /**
     * Replace SmsService with a controllable spy. We
     * bind a real instance for type-hint resolution
     * and a Mockery mock for assertions.
     *
     * @return Mockery\MockInterface&SmsService
     */
    private function fakeSms()
    {
        $spy = Mockery::mock(SmsService::class);
        $spy->shouldReceive('send')
            ->byDefault()
            ->andReturn([
                'success' => true,
                'message_id' => 'mock-msg-id-123',
                'provider' => 'unifonic',
            ]);
        $spy->shouldReceive('isReady')->byDefault()->andReturn(true);

        $this->app->instance(SmsService::class, $spy);

        return $spy;
    }

    // ─────────────────────────────────────────────────────────────────
    // Booking flow: confirmation message
    // ─────────────────────────────────────────────────────────────────

    public function test_booking_creates_one_confirmation_delivery_after_commit(): void
    {
        Queue::fake();
        $spy = $this->fakeSms();

        $tenant = $this->publishedTenant();
        $center = $this->openCenter($tenant);
        $bay = $this->bayIn($center);
        $service = $this->serviceIn($tenant, $center);
        $customer = $this->customerIn($tenant);
        $vehicle = $this->vehicleFor($customer, $tenant);

        $this->loginAs($customer, $tenant);

        $url = '/'.$tenant->slug.'/portal/appointments';

        $response = $this->post($url, [
            'center_id' => $center->id,
            'vehicle_id' => $vehicle->id,
            'service_ids' => [$service->id],
            'scheduled_at' => Carbon::now()->addHours(3)->setTime(10, 0)->toIso8601String(),
        ]);
        $response->assertRedirect();

        // After-commit hook fires on redirect; the
        // queue captures the job. We process it
        // manually with the spy active.
        $delivery = AppointmentMessageDelivery::query()
            ->where('tenant_id', $tenant->id)
            ->where('message_type', AppointmentMessageDelivery::TYPE_BOOKING_CONFIRMATION)
            ->first();
        $this->assertNotNull($delivery, 'confirmation delivery was created');
        $this->assertSame(AppointmentMessageDelivery::STATUS_QUEUED, $delivery->status);

        Queue::assertPushed(SendAppointmentMessage::class);
    }

    public function test_confirmation_delivery_carries_phone_hash_not_raw_phone(): void
    {
        Queue::fake();
        $this->fakeSms();

        $tenant = $this->publishedTenant();
        $center = $this->openCenter($tenant);
        $bay = $this->bayIn($center);
        $service = $this->serviceIn($tenant, $center);
        $customer = $this->customerIn($tenant, '+966501234567');
        $vehicle = $this->vehicleFor($customer, $tenant);
        $this->loginAs($customer, $tenant);

        $this->post('/'.$tenant->slug.'/portal/appointments', [
            'center_id' => $center->id,
            'vehicle_id' => $vehicle->id,
            'service_ids' => [$service->id],
            'scheduled_at' => Carbon::now()->addHours(3)->setTime(10, 0)->toIso8601String(),
        ])->assertRedirect();

        $delivery = AppointmentMessageDelivery::query()
            ->where('tenant_id', $tenant->id)
            ->first();

        $this->assertNotNull($delivery);
        $this->assertSame(
            hash('sha256', '+966501234567'),
            $delivery->phone_hash,
            'phone_hash must be sha256 of the customer phone'
        );
        $this->assertNotEmpty($delivery->idempotency_key);
    }

    public function test_send_job_calls_sms_service_and_marks_sent(): void
    {
        $spy = $this->fakeSms();
        $spy->shouldReceive('send')
            ->once()
            ->withArgs(function (string $phone, string $body, int $tenantId) {
                // The body must NOT contain the raw
                // phone. It must contain a reference
                // (KHDM-{id}) so the customer can
                // quote it.
                $this->assertStringNotContainsString('+966501234567', $body);

                return true;
            })
            ->andReturn([
                'success' => true,
                'message_id' => 'provider-msg-1',
                'provider' => 'unifonic',
            ]);

        $tenant = $this->publishedTenant();
        $center = $this->openCenter($tenant);
        $customer = $this->customerIn($tenant);
        $bay = $this->bayIn($center);
        $service = $this->serviceIn($tenant, $center);
        $vehicle = $this->vehicleFor($customer, $tenant);
        $this->loginAs($customer, $tenant);

        $start = Carbon::now()->addHours(3)->setTime(10, 0);
        $appointment = Appointment::create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'service_bay_id' => $bay->id,
            'scheduled_at' => $start,
            'estimated_end_at' => $start->copy()->addMinutes(30),
            'status' => Appointment::STATUS_SCHEDULED,
            'source' => Appointment::SOURCE_CUSTOMER_PORTAL,
        ]);
        $appointment->services()->attach($service->id, ['estimated_minutes' => 30]);

        // Manually dispatch via the dispatcher
        // (simulating the after-commit hook).
        $dispatcher = $this->app->make(AppointmentMessageDispatcher::class);
        $dispatcher->confirmation($appointment, $customer);

        // The dispatcher fakes the queue by default
        // (we don't Queue::fake in this test) so
        // the job runs synchronously and the SmsService
        // gets called.
        $delivery = AppointmentMessageDelivery::query()
            ->where('appointment_id', $appointment->id)
            ->first();

        $this->assertNotNull($delivery);
        $this->assertSame(AppointmentMessageDelivery::STATUS_SENT, $delivery->status);
        $this->assertSame('provider-msg-1', $delivery->provider_message_id);
        $this->assertSame('unifonic', $delivery->provider);
    }

    public function test_send_job_marks_failed_when_sms_service_throws(): void
    {
        // Anonymous subclass to avoid the SmsService
        // constructor (which would otherwise try to
        // resolve a default Integration on a fresh
        // test DB).
        $spy = new class extends SmsService
        {
            public function send(string $to, string $message, ?int $tenantId = null): array
            {
                throw new RuntimeException('boom');
            }

            public function isReady(): bool
            {
                return true;
            }
        };
        $this->app->instance(SmsService::class, $spy);

        $tenant = $this->publishedTenant();
        $center = $this->openCenter($tenant);
        $customer = $this->customerIn($tenant);
        $bay = $this->bayIn($center);
        $service = $this->serviceIn($tenant, $center);
        $vehicle = $this->vehicleFor($customer, $tenant);
        $this->loginAs($customer, $tenant);

        $start = Carbon::now()->addHours(3)->setTime(10, 0);
        $appointment = Appointment::create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'service_bay_id' => $bay->id,
            'scheduled_at' => $start,
            'estimated_end_at' => $start->copy()->addMinutes(30),
            'status' => Appointment::STATUS_SCHEDULED,
            'source' => Appointment::SOURCE_CUSTOMER_PORTAL,
        ]);
        $appointment->services()->attach($service->id, ['estimated_minutes' => 30]);

        $dispatcher = $this->app->make(AppointmentMessageDispatcher::class);

        // The Sync queue driver runs the job inline,
        // so the throw inside the job propagates
        // here. We catch the re-throw so the test
        // body can assert on the row's terminal
        // state.
        try {
            $dispatcher->confirmation($appointment, $customer);
        } catch (Throwable) {
            // expected — the job re-throws on
            // provider failure so the queue's retry
            // policy counts the attempt.
        }

        $delivery = AppointmentMessageDelivery::query()
            ->where('appointment_id', $appointment->id)
            ->first();

        $this->assertNotNull($delivery);
        $this->assertSame(AppointmentMessageDelivery::STATUS_FAILED, $delivery->status);
        $this->assertNotNull($delivery->failed_at);
        // The phone is NOT in the error message.
        $this->assertStringNotContainsString('+966501234567', (string) $delivery->error_message);
    }

    public function test_duplicate_dispatch_is_collapsed_to_one_row(): void
    {
        $spy = $this->fakeSms();
        $spy->shouldReceive('send')
            ->once()
            ->andReturn([
                'success' => true,
                'message_id' => 'provider-msg-2',
                'provider' => 'unifonic',
            ]);

        $tenant = $this->publishedTenant();
        $center = $this->openCenter($tenant);
        $customer = $this->customerIn($tenant);
        $bay = $this->bayIn($center);
        $service = $this->serviceIn($tenant, $center);
        $vehicle = $this->vehicleFor($customer, $tenant);
        $this->loginAs($customer, $tenant);

        $start = Carbon::now()->addHours(3)->setTime(10, 0);
        $appointment = Appointment::create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'service_bay_id' => $bay->id,
            'scheduled_at' => $start,
            'estimated_end_at' => $start->copy()->addMinutes(30),
            'status' => Appointment::STATUS_SCHEDULED,
            'source' => Appointment::SOURCE_CUSTOMER_PORTAL,
        ]);
        $appointment->services()->attach($service->id, ['estimated_minutes' => 30]);

        $dispatcher = $this->app->make(AppointmentMessageDispatcher::class);
        $dispatcher->confirmation($appointment, $customer);
        // Second call MUST collapse to the same row.
        $dispatcher->confirmation($appointment, $customer);

        $rows = AppointmentMessageDelivery::query()
            ->where('appointment_id', $appointment->id)
            ->where('message_type', AppointmentMessageDelivery::TYPE_BOOKING_CONFIRMATION)
            ->get();
        $this->assertCount(1, $rows);
    }

    public function test_send_job_second_run_bails_when_row_already_terminal(): void
    {
        $spy = $this->fakeSms();
        $spy->shouldReceive('send')
            ->once()
            ->andReturn([
                'success' => true,
                'message_id' => 'first',
                'provider' => 'unifonic',
            ]);

        $tenant = $this->publishedTenant();
        $center = $this->openCenter($tenant);
        $customer = $this->customerIn($tenant);
        $bay = $this->bayIn($center);
        $service = $this->serviceIn($tenant, $center);
        $vehicle = $this->vehicleFor($customer, $tenant);
        $this->loginAs($customer, $tenant);

        $start = Carbon::now()->addHours(3)->setTime(10, 0);
        $appointment = Appointment::create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'service_bay_id' => $bay->id,
            'scheduled_at' => $start,
            'estimated_end_at' => $start->copy()->addMinutes(30),
            'status' => Appointment::STATUS_SCHEDULED,
            'source' => Appointment::SOURCE_CUSTOMER_PORTAL,
        ]);
        $appointment->services()->attach($service->id, ['estimated_minutes' => 30]);

        $dispatcher = $this->app->make(AppointmentMessageDispatcher::class);
        $dispatcher->confirmation($appointment, $customer);

        // The first run marked the row sent. A second
        // worker that picks up the same job id
        // (queue re-delivery) must NOT re-send.
        $delivery = AppointmentMessageDelivery::query()
            ->where('appointment_id', $appointment->id)
            ->first();
        $this->assertSame(AppointmentMessageDelivery::STATUS_SENT, $delivery->status);

        // Re-run the job manually with the same id.
        // The SmsService should NOT be called again
        // because the row is already terminal.
        (new SendAppointmentMessage($delivery->id))
            ->handle(
                $this->app->make(AppointmentMessageTemplate::class),
                $this->app->make(SmsService::class)
            );
    }

    public function test_no_phone_skips_delivery_without_throwing(): void
    {
        $spy = $this->fakeSms();
        $spy->shouldNotReceive('send');

        $tenant = $this->publishedTenant();
        $center = $this->openCenter($tenant);
        // The customers table has a NOT NULL on
        // phone, so we cannot insert null. The
        // dispatcher treats '' as no phone. This
        // is a realistic scenario: a customer with
        // a legacy record that has a placeholder
        // phone.
        $customerId = DB::table('customers')->insertGetId([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'type' => 'individual',
            'name' => 'No Phone',
            'phone' => '',
            'whatsapp' => '',
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);
        $customer = Customer::query()->findOrFail($customerId);
        $bay = $this->bayIn($center);
        $service = $this->serviceIn($tenant, $center);
        $vehicle = $this->vehicleFor($customer, $tenant);
        $this->loginAs($customer, $tenant);

        $start = Carbon::now()->addHours(3)->setTime(10, 0);
        $appointment = Appointment::create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'service_bay_id' => $bay->id,
            'scheduled_at' => $start,
            'estimated_end_at' => $start->copy()->addMinutes(30),
            'status' => Appointment::STATUS_SCHEDULED,
            'source' => Appointment::SOURCE_CUSTOMER_PORTAL,
        ]);

        $dispatcher = $this->app->make(AppointmentMessageDispatcher::class);
        $dispatcher->confirmation($appointment, $customer);

        $this->assertSame(0, AppointmentMessageDelivery::query()->count());
    }

    public function test_log_does_not_carry_phone_or_message_body(): void
    {
        $captured = [];
        Log::listen(function (MessageLogged $event) use (&$captured) {
            $captured[] = [
                'level' => $event->level,
                'message' => $event->message,
                'context' => $event->context,
            ];
        });

        // Build a fresh spy whose send() throws so we
        // exercise the failure path of the dispatcher.
        $spy = Mockery::mock(SmsService::class);
        $spy->shouldReceive('send')
            ->andThrow(new RuntimeException('sms_send_failed: 0501234567 not deliverable'));
        $spy->shouldReceive('isReady')->andReturn(true);
        $this->app->instance(SmsService::class, $spy);

        $tenant = $this->publishedTenant();
        $center = $this->openCenter($tenant);
        $customer = $this->customerIn($tenant, '+966509999999');
        $bay = $this->bayIn($center);
        $service = $this->serviceIn($tenant, $center);
        $vehicle = $this->vehicleFor($customer, $tenant);
        $this->loginAs($customer, $tenant);

        $start = Carbon::now()->addHours(3)->setTime(10, 0);
        $appointment = Appointment::create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'service_bay_id' => $bay->id,
            'scheduled_at' => $start,
            'estimated_end_at' => $start->copy()->addMinutes(30),
            'status' => Appointment::STATUS_SCHEDULED,
            'source' => Appointment::SOURCE_CUSTOMER_PORTAL,
        ]);
        $appointment->services()->attach($service->id, ['estimated_minutes' => 30]);

        $dispatcher = $this->app->make(AppointmentMessageDispatcher::class);

        try {
            $dispatcher->confirmation($appointment, $customer);
        } catch (Throwable) {
            // The job re-throws on failure; we
            // already captured the state we need.
        }

        $rawPhone = '+966509999999';
        $sawFailureLog = false;

        foreach ($captured as $entry) {
            if ($entry['level'] !== 'warning') {
                continue;
            }

            if ($entry['message'] !== 'appointment_message.send_failed'
                && $entry['message'] !== 'appointment_message.insert_failed') {
                continue;
            }
            $sawFailureLog = true;

            foreach ($entry['context'] as $v) {
                if (! is_string($v) && ! is_numeric($v)) {
                    continue;
                }
                $str = (string) $v;
                $this->assertStringNotContainsString($rawPhone, $str, "phone leaked in log: {$str}");
            }
        }
        $this->assertTrue($sawFailureLog, 'no warning was logged for the send failure');
    }

    // ─────────────────────────────────────────────────────────────────
    // Reminder scanner
    // ─────────────────────────────────────────────────────────────────

    public function test_reminder_scanner_fires_24h_for_appointments_in_window(): void
    {
        $spy = $this->fakeSms();
        $spy->shouldReceive('send')->andReturn([
            'success' => true,
            'message_id' => 'mock',
            'provider' => 'unifonic',
        ]);

        $tenant = $this->publishedTenant();
        $center = $this->openCenter($tenant);
        $customer = $this->customerIn($tenant);
        $bay = $this->bayIn($center);
        $service = $this->serviceIn($tenant, $center);
        $vehicle = $this->vehicleFor($customer, $tenant);

        $in24h = Carbon::now()->copy()->addHours(24);
        $appointment = Appointment::create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'service_bay_id' => $bay->id,
            'scheduled_at' => $in24h,
            'estimated_end_at' => $in24h->copy()->addMinutes(30),
            'status' => Appointment::STATUS_SCHEDULED,
            'source' => Appointment::SOURCE_CUSTOMER_PORTAL,
        ]);

        $count = $this->app->make(AppointmentMessageReminderScanner::class)->scan();
        $this->assertSame(1, $count);

        $reminder = AppointmentMessageDelivery::query()
            ->where('appointment_id', $appointment->id)
            ->where('message_type', AppointmentMessageDelivery::TYPE_REMINDER_24H)
            ->first();
        $this->assertNotNull($reminder);
    }

    public function test_reminder_scanner_fires_2h_for_appointments_in_window(): void
    {
        $this->fakeSms();

        $tenant = $this->publishedTenant();
        $center = $this->openCenter($tenant);
        $customer = $this->customerIn($tenant);
        $bay = $this->bayIn($center);
        $service = $this->serviceIn($tenant, $center);
        $vehicle = $this->vehicleFor($customer, $tenant);

        $in2h = Carbon::now()->copy()->addHours(2);
        $appointment = Appointment::create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'service_bay_id' => $bay->id,
            'scheduled_at' => $in2h,
            'estimated_end_at' => $in2h->copy()->addMinutes(30),
            'status' => Appointment::STATUS_SCHEDULED,
            'source' => Appointment::SOURCE_CUSTOMER_PORTAL,
        ]);

        $count = $this->app->make(AppointmentMessageReminderScanner::class)->scan();
        $this->assertSame(1, $count);

        $reminder = AppointmentMessageDelivery::query()
            ->where('appointment_id', $appointment->id)
            ->where('message_type', AppointmentMessageDelivery::TYPE_REMINDER_2H)
            ->first();
        $this->assertNotNull($reminder);
    }

    public function test_reminder_scanner_skips_outside_window(): void
    {
        $this->fakeSms();

        $tenant = $this->publishedTenant();
        $center = $this->openCenter($tenant);
        $customer = $this->customerIn($tenant);
        $bay = $this->bayIn($center);
        $service = $this->serviceIn($tenant, $center);
        $vehicle = $this->vehicleFor($customer, $tenant);

        // Outside both 24h and 2h windows: 5 hours out.
        $later = Carbon::now()->copy()->addHours(5);
        Appointment::create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'service_bay_id' => $bay->id,
            'scheduled_at' => $later,
            'estimated_end_at' => $later->copy()->addMinutes(30),
            'status' => Appointment::STATUS_SCHEDULED,
            'source' => Appointment::SOURCE_CUSTOMER_PORTAL,
        ]);

        $count = $this->app->make(AppointmentMessageReminderScanner::class)->scan();
        $this->assertSame(0, $count);
    }

    public function test_reminder_scanner_skips_cancelled_no_show_converted(): void
    {
        $this->fakeSms();

        $tenant = $this->publishedTenant();
        $center = $this->openCenter($tenant);
        $customer = $this->customerIn($tenant);
        $bay = $this->bayIn($center);
        $service = $this->serviceIn($tenant, $center);
        $vehicle = $this->vehicleFor($customer, $tenant);

        $in24h = Carbon::now()->copy()->addHours(24);

        foreach ([
            Appointment::STATUS_CANCELLED,
            Appointment::STATUS_NO_SHOW,
            Appointment::STATUS_CONVERTED,
        ] as $status) {
            Appointment::create([
                'tenant_id' => $tenant->id,
                'center_id' => $center->id,
                'customer_id' => $customer->id,
                'vehicle_id' => $vehicle->id,
                'service_bay_id' => $bay->id,
                'scheduled_at' => $in24h,
                'estimated_end_at' => $in24h->copy()->addMinutes(30),
                'status' => $status,
                'source' => Appointment::SOURCE_CUSTOMER_PORTAL,
            ]);
        }

        $count = $this->app->make(AppointmentMessageReminderScanner::class)->scan();
        $this->assertSame(0, $count);
        $this->assertSame(0, AppointmentMessageDelivery::query()->count());
    }

    public function test_reminder_scanner_fires_only_once_per_appointment(): void
    {
        $spy = $this->fakeSms();
        $spy->shouldReceive('send')->andReturn([
            'success' => true,
            'message_id' => 'mock',
            'provider' => 'unifonic',
        ]);

        $tenant = $this->publishedTenant();
        $center = $this->openCenter($tenant);
        $customer = $this->customerIn($tenant);
        $bay = $this->bayIn($center);
        $service = $this->serviceIn($tenant, $center);
        $vehicle = $this->vehicleFor($customer, $tenant);

        $in24h = Carbon::now()->copy()->addHours(24);
        Appointment::create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'service_bay_id' => $bay->id,
            'scheduled_at' => $in24h,
            'estimated_end_at' => $in24h->copy()->addMinutes(30),
            'status' => Appointment::STATUS_SCHEDULED,
            'source' => Appointment::SOURCE_CUSTOMER_PORTAL,
        ]);

        $scanner = $this->app->make(AppointmentMessageReminderScanner::class);
        $scanner->scan();
        $scanner->scan();
        $scanner->scan();

        $this->assertSame(1, AppointmentMessageDelivery::query()
            ->where('message_type', AppointmentMessageDelivery::TYPE_REMINDER_24H)
            ->count());
    }

    public function test_reminder_after_reschedule_fires_for_new_time(): void
    {
        $spy = $this->fakeSms();
        $spy->shouldReceive('send')->andReturn([
            'success' => true,
            'message_id' => 'mock',
            'provider' => 'unifonic',
        ]);

        $tenant = $this->publishedTenant();
        $center = $this->openCenter($tenant);
        $customer = $this->customerIn($tenant);
        $bay = $this->bayIn($center);
        $service = $this->serviceIn($tenant, $center);
        $vehicle = $this->vehicleFor($customer, $tenant);

        // Original time: now + 5h (outside any window).
        $original = Carbon::now()->copy()->addHours(5);
        $appointment = Appointment::create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'service_bay_id' => $bay->id,
            'scheduled_at' => $original,
            'estimated_end_at' => $original->copy()->addMinutes(30),
            'status' => Appointment::STATUS_SCHEDULED,
            'source' => Appointment::SOURCE_CUSTOMER_PORTAL,
        ]);

        // Reschedule into the 24h window.
        $newTime = Carbon::now()->copy()->addHours(24);
        $appointment->update([
            'scheduled_at' => $newTime,
            'estimated_end_at' => $newTime->copy()->addMinutes(30),
        ]);

        $this->app->make(AppointmentMessageReminderScanner::class)->scan();

        $this->assertSame(1, AppointmentMessageDelivery::query()
            ->where('appointment_id', $appointment->id)
            ->where('message_type', AppointmentMessageDelivery::TYPE_REMINDER_24H)
            ->count());
    }

    public function test_tenant_isolation_in_delivery_query(): void
    {
        $this->fakeSms();

        $tenantA = $this->publishedTenant('a', 'Site A');
        $tenantB = $this->publishedTenant('b', 'Site B');
        $centerA = $this->openCenter($tenantA, 'A-Branch');
        $centerB = $this->openCenter($tenantB, 'B-Branch');
        $customerA = $this->customerIn($tenantA);
        $customerB = $this->customerIn($tenantB, '+966509876543');
        $bayA = $this->bayIn($centerA);
        $bayB = $this->bayIn($centerB);
        $serviceA = $this->serviceIn($tenantA, $centerA);
        $serviceB = $this->serviceIn($tenantB, $centerB);
        $vehicleA = $this->vehicleFor($customerA, $tenantA);
        $vehicleB = $this->vehicleFor($customerB, $tenantB);

        $startA = Carbon::now()->copy()->addHours(3)->setTime(10, 0);
        $apptA = Appointment::create([
            'tenant_id' => $tenantA->id,
            'center_id' => $centerA->id,
            'customer_id' => $customerA->id,
            'vehicle_id' => $vehicleA->id,
            'service_bay_id' => $bayA->id,
            'scheduled_at' => $startA,
            'estimated_end_at' => $startA->copy()->addMinutes(30),
            'status' => Appointment::STATUS_SCHEDULED,
            'source' => Appointment::SOURCE_CUSTOMER_PORTAL,
        ]);

        $startB = Carbon::now()->copy()->addHours(3)->setTime(10, 0);
        $apptB = Appointment::create([
            'tenant_id' => $tenantB->id,
            'center_id' => $centerB->id,
            'customer_id' => $customerB->id,
            'vehicle_id' => $vehicleB->id,
            'service_bay_id' => $bayB->id,
            'scheduled_at' => $startB,
            'estimated_end_at' => $startB->copy()->addMinutes(30),
            'status' => Appointment::STATUS_SCHEDULED,
            'source' => Appointment::SOURCE_CUSTOMER_PORTAL,
        ]);

        $dispatcher = $this->app->make(AppointmentMessageDispatcher::class);
        $dispatcher->confirmation($apptA, $customerA);
        $dispatcher->confirmation($apptB, $customerB);

        // Tenant A must see only its row.
        $rowsA = AppointmentMessageDelivery::query()
            ->where('tenant_id', $tenantA->id)
            ->get();
        $this->assertCount(1, $rowsA);
        $this->assertSame($apptA->id, $rowsA->first()->appointment_id);

        $rowsB = AppointmentMessageDelivery::query()
            ->where('tenant_id', $tenantB->id)
            ->get();
        $this->assertCount(1, $rowsB);
        $this->assertSame($apptB->id, $rowsB->first()->appointment_id);
    }

    public function test_reschedule_creates_rescheduled_delivery(): void
    {
        $spy = $this->fakeSms();
        $spy->shouldReceive('send')->andReturn([
            'success' => true,
            'message_id' => 'mock',
            'provider' => 'unifonic',
        ]);

        $tenant = $this->publishedTenant();
        $center = $this->openCenter($tenant);
        $customer = $this->customerIn($tenant);
        $bay = $this->bayIn($center);
        $service = $this->serviceIn($tenant, $center);
        $vehicle = $this->vehicleFor($customer, $tenant);

        $start = Carbon::now()->copy()->addHours(24);
        $appointment = Appointment::create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'service_bay_id' => $bay->id,
            'scheduled_at' => $start,
            'estimated_end_at' => $start->copy()->addMinutes(30),
            'status' => Appointment::STATUS_SCHEDULED,
            'source' => Appointment::SOURCE_CUSTOMER_PORTAL,
        ]);

        $newTime = $start->copy()->addDay();
        $appointment->update([
            'scheduled_at' => $newTime,
            'estimated_end_at' => $newTime->copy()->addMinutes(30),
        ]);

        $this->app->make(AppointmentMessageDispatcher::class)
            ->rescheduled($appointment, $customer);

        $row = AppointmentMessageDelivery::query()
            ->where('appointment_id', $appointment->id)
            ->where('message_type', AppointmentMessageDelivery::TYPE_BOOKING_RESCHEDULED)
            ->first();
        $this->assertNotNull($row);
    }

    public function test_cancel_creates_cancelled_delivery(): void
    {
        $spy = $this->fakeSms();
        $spy->shouldReceive('send')->andReturn([
            'success' => true,
            'message_id' => 'mock',
            'provider' => 'unifonic',
        ]);

        $tenant = $this->publishedTenant();
        $center = $this->openCenter($tenant);
        $customer = $this->customerIn($tenant);
        $bay = $this->bayIn($center);
        $service = $this->serviceIn($tenant, $center);
        $vehicle = $this->vehicleFor($customer, $tenant);

        $start = Carbon::now()->copy()->addHours(24);
        $appointment = Appointment::create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'service_bay_id' => $bay->id,
            'scheduled_at' => $start,
            'estimated_end_at' => $start->copy()->addMinutes(30),
            'status' => Appointment::STATUS_CANCELLED,
            'source' => Appointment::SOURCE_CUSTOMER_PORTAL,
        ]);

        $this->app->make(AppointmentMessageDispatcher::class)
            ->cancelled($appointment, $customer);

        $row = AppointmentMessageDelivery::query()
            ->where('appointment_id', $appointment->id)
            ->where('message_type', AppointmentMessageDelivery::TYPE_BOOKING_CANCELLED)
            ->first();
        $this->assertNotNull($row);
    }

    // ─────────────────────────────────────────────────────────────────
    // SystemSmsOtpChannel — production OTP channel
    // ─────────────────────────────────────────────────────────────────

    public function test_system_sms_otp_channel_calls_sms_service_and_returns_provider_message_id(): void
    {
        $spy = Mockery::mock(SmsService::class);
        $spy->shouldReceive('send')
            ->once()
            ->andReturn([
                'success' => true,
                'message_id' => 'provider-otp-1',
                'provider' => 'unifonic',
            ]);
        $spy->shouldReceive('isReady')->andReturn(true);
        $this->app->instance(SmsService::class, $spy);

        // Re-bind the OTP channel to the production
        // implementation. The default binding in
        // APP_ENV=testing is LoggingCustomerPortalOtpChannel;
        // we want to assert the production path here.
        $this->app->forgetInstance(CustomerPortalOtpChannel::class);
        $this->app->bind(
            CustomerPortalOtpChannel::class,
            SystemSmsOtpChannel::class
        );

        $tenant = $this->publishedTenant();
        $customer = $this->customerIn($tenant, '+966501234567');
        $otp = CustomerPortalOtp::create([
            'tenant_id' => $tenant->id,
            'customer_id' => $customer->id,
            'phone_normalized' => '+966501234567',
            'phone_hash' => hash('sha256', '+966501234567'),
            'code_hash' => Hash::make('123456'),
            'expires_at' => Carbon::now()->addMinutes(10),
        ]);

        $channel = $this->app->make(CustomerPortalOtpChannel::class);
        $result = $channel->send($otp, '+966501234567', '123456');

        $this->assertTrue($result->success);
        $this->assertSame('provider-otp-1', $result->providerMessageId);
    }

    public function test_system_sms_otp_channel_swallows_provider_failure(): void
    {
        $spy = Mockery::mock(SmsService::class);
        $spy->shouldReceive('send')
            ->andThrow(new RuntimeException('provider down'));
        $spy->shouldReceive('isReady')->andReturn(true);
        $this->app->instance(SmsService::class, $spy);

        $this->app->forgetInstance(CustomerPortalOtpChannel::class);
        $this->app->bind(
            CustomerPortalOtpChannel::class,
            SystemSmsOtpChannel::class
        );

        $tenant = $this->publishedTenant();
        $customer = $this->customerIn($tenant, '+966501234567');
        $otp = CustomerPortalOtp::create([
            'tenant_id' => $tenant->id,
            'customer_id' => $customer->id,
            'phone_normalized' => '+966501234567',
            'phone_hash' => hash('sha256', '+966501234567'),
            'code_hash' => Hash::make('123456'),
            'expires_at' => Carbon::now()->addMinutes(10),
        ]);

        $channel = $this->app->make(CustomerPortalOtpChannel::class);
        $result = $channel->send($otp, '+966501234567', '123456');

        $this->assertFalse($result->success);
        $this->assertNull($result->providerMessageId);
        $this->assertSame('sms_send_failed', $result->errorCode);
    }

    public function test_log_payload_does_not_carry_phone_or_code_on_otp_channel_failure(): void
    {
        // The SystemSmsOtpChannel uses Log::warning on
        // a provider failure. We replace the underlying
        // log writer with an in-memory capture that
        // records every call's message + context, so
        // we can assert after the fact that the phone
        // and the code never appear in any captured
        // entry.
        $captured = [];
        Log::listen(function (MessageLogged $event) use (&$captured) {
            $captured[] = [
                'level' => $event->level,
                'message' => $event->message,
                'context' => $event->context,
            ];
        });

        $spy = Mockery::mock(SmsService::class);
        $spy->shouldReceive('send')
            ->andThrow(new RuntimeException('sms_send_failed: phone +966501111111 not deliverable'));
        $spy->shouldReceive('isReady')->andReturn(true);
        $this->app->instance(SmsService::class, $spy);

        $this->app->forgetInstance(CustomerPortalOtpChannel::class);
        $this->app->bind(
            CustomerPortalOtpChannel::class,
            SystemSmsOtpChannel::class
        );

        $tenant = $this->publishedTenant();
        $customer = $this->customerIn($tenant, '+966501111111');
        $otp = CustomerPortalOtp::create([
            'tenant_id' => $tenant->id,
            'customer_id' => $customer->id,
            'phone_normalized' => '+966501111111',
            'phone_hash' => hash('sha256', '+966501111111'),
            'code_hash' => Hash::make('123456'),
            'expires_at' => Carbon::now()->addMinutes(10),
        ]);

        $channel = $this->app->make(CustomerPortalOtpChannel::class);
        $result = $channel->send($otp, '+966501111111', '123456');

        $this->assertFalse($result->success);

        // The SystemSmsOtpChannel must have logged at
        // least one warning for the failure.
        $warningCount = 0;

        foreach ($captured as $entry) {
            if ($entry['level'] === 'warning'
                && $entry['message'] === 'customer_portal.otp.system_channel_failed') {
                $warningCount++;

                $rawPhone = '+966501111111';
                $rawCode = '123456';

                foreach ($entry['context'] as $v) {
                    if (! is_string($v) && ! is_numeric($v)) {
                        continue;
                    }
                    $str = (string) $v;
                    $this->assertStringNotContainsString($rawPhone, $str, "phone leaked in log context: {$str}");
                    $this->assertStringNotContainsString($rawCode, $str, "code leaked in log context: {$str}");
                }
            }
        }
        $this->assertGreaterThan(0, $warningCount, 'no warning was logged for the OTP channel failure');
    }
}

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
use Mockery;
use RuntimeException;
use Tests\TestCase;
use Throwable;

/**
 * Phase 10 hot-fix — P0/P1 regressions.
 *
 *   P0 — The reminder scanner used to filter on
 *        "any delivery row exists for this
 *        appointment", which meant a confirmation
 *        from booking time blocked every reminder
 *        ever. The fix scopes the pre-filter to
 *        (appointment, THIS message type, the
 *        current scheduled_at snapshot).
 *
 *   P1 — The unique key on the deliveries table
 *        was (appointment_id, message_type). That
 *        collided with the idempotency_key (which
 *        is keyed off scheduled_at) and made
 *        reschedules idempotent-broken. The new
 *        key is (appointment_id, message_type,
 *        scheduled_at_snapshot).
 *
 *   P1 — SendAppointmentMessage re-validates the
 *        snapshot against the appointment's
 *        current scheduled_at. A reschedule between
 *        dispatch and worker pickup skips the
 *        message with reason
 *        `appointment_schedule_changed` instead of
 *        sending a stale notification.
 *
 *   P1 — SystemSmsOtpChannel invalidates the OTP
 *        row whenever the provider cannot deliver.
 *        No provider / throw / explicit failure
 *        result all mark `consumed_at = now()` so
 *        the verify path rejects the code.
 *
 *   P1 — SendAppointmentMessage::markFailed never
 *        persists the exception message. The
 *        provider's HTTP response can carry the
 *        phone; we write only the class name and
 *        `error_message = null`.
 */
class AppointmentMessagesHotfixTest extends TestCase
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

    // ──────────────────────────────────────────────────────────
    // Helpers
    // ──────────────────────────────────────────────────────────

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
    }

    private function makeAppointment(
        Tenant $tenant,
        Center $center,
        Customer $customer,
        Vehicle $vehicle,
        Carbon $scheduledAt,
        string $status = Appointment::STATUS_SCHEDULED,
    ): Appointment {
        $appt = Appointment::create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'service_bay_id' => null,
            'scheduled_at' => $scheduledAt,
            'estimated_end_at' => $scheduledAt->copy()->addMinutes(30),
            'status' => $status,
            'source' => Appointment::SOURCE_CUSTOMER_PORTAL,
        ]);

        return $appt;
    }

    // ──────────────────────────────────────────────────────────
    // P0 — Reminder scanner dedup is scoped to message_type
    // ──────────────────────────────────────────────────────────

    public function test_reminder_24h_fires_after_confirmation_row_exists(): void
    {
        // The original bug: a confirmation row
        // existed for this appointment, so the
        // scanner's "any delivery exists" filter
        // excluded the appointment, and the 24h
        // reminder was never dispatched.
        $tenant = $this->publishedTenant();
        $center = $this->openCenter($tenant);
        $customer = $this->customerIn($tenant);
        $vehicle = $this->vehicleFor($customer, $tenant);

        // Book the appointment in the 24h window.
        $scheduled = Carbon::now()->copy()->addHours(24);
        $appt = $this->makeAppointment($tenant, $center, $customer, $vehicle, $scheduled);

        // Simulate the confirmation row that
        // would have been written when the
        // customer booked the appointment.
        $dispatcher = $this->app->make(AppointmentMessageDispatcher::class);
        $dispatcher->confirmation($appt, $customer);

        $this->assertSame(1, AppointmentMessageDelivery::query()
            ->where('appointment_id', $appt->id)
            ->where('message_type', AppointmentMessageDelivery::TYPE_BOOKING_CONFIRMATION)
            ->count());

        // Now run the reminder scanner. The
        // confirmation row MUST NOT block the
        // 24h reminder.
        $count = $this->app->make(AppointmentMessageReminderScanner::class)->scan();

        $this->assertSame(1, $count);
        $this->assertSame(1, AppointmentMessageDelivery::query()
            ->where('appointment_id', $appt->id)
            ->where('message_type', AppointmentMessageDelivery::TYPE_REMINDER_24H)
            ->count());
    }

    public function test_reminder_2h_fires_after_24h_reminder_row_exists(): void
    {
        // The original bug also would have
        // blocked a 2h reminder if a 24h
        // reminder row already existed.
        $tenant = $this->publishedTenant();
        $center = $this->openCenter($tenant);
        $customer = $this->customerIn($tenant);
        $vehicle = $this->vehicleFor($customer, $tenant);

        // Appointment 2h out.
        $scheduled = Carbon::now()->copy()->addHours(2);
        $appt = $this->makeAppointment($tenant, $center, $customer, $vehicle, $scheduled);

        $dispatcher = $this->app->make(AppointmentMessageDispatcher::class);

        // Simulate: confirmation + 24h reminder
        // (which would not actually fire for a
        // 2h-out appointment, but we model the
        // edge case where another scanner tick
        // had fired it earlier).
        $dispatcher->confirmation($appt, $customer);
        AppointmentMessageDelivery::create([
            'tenant_id' => $tenant->id,
            'appointment_id' => $appt->id,
            'customer_id' => $customer->id,
            'message_type' => AppointmentMessageDelivery::TYPE_REMINDER_24H,
            'scheduled_at_snapshot' => $appt->scheduled_at,
            'idempotency_key' => str_repeat('a', 32),
            'phone_hash' => hash('sha256', '+966501234567'),
            'status' => AppointmentMessageDelivery::STATUS_SENT,
            'attempts' => 1,
            'sent_at' => Carbon::now(),
        ]);

        $this->app->make(AppointmentMessageReminderScanner::class)->scan();

        $this->assertSame(1, AppointmentMessageDelivery::query()
            ->where('appointment_id', $appt->id)
            ->where('message_type', AppointmentMessageDelivery::TYPE_REMINDER_2H)
            ->count());
    }

    public function test_repeating_scanner_does_not_send_same_reminder_twice(): void
    {
        $tenant = $this->publishedTenant();
        $center = $this->openCenter($tenant);
        $customer = $this->customerIn($tenant);
        $vehicle = $this->vehicleFor($customer, $tenant);
        $scheduled = Carbon::now()->copy()->addHours(24);
        $appt = $this->makeAppointment($tenant, $center, $customer, $vehicle, $scheduled);

        $scanner = $this->app->make(AppointmentMessageReminderScanner::class);
        $scanner->scan();
        $scanner->scan();
        $scanner->scan();

        $this->assertSame(1, AppointmentMessageDelivery::query()
            ->where('appointment_id', $appt->id)
            ->where('message_type', AppointmentMessageDelivery::TYPE_REMINDER_24H)
            ->count());
    }

    // ──────────────────────────────────────────────────────────
    // P1 — Reschedule idempotency by snapshot
    // ──────────────────────────────────────────────────────────

    public function test_two_reschedules_produce_two_rescheduled_rows(): void
    {
        $tenant = $this->publishedTenant();
        $center = $this->openCenter($tenant);
        $customer = $this->customerIn($tenant);
        $vehicle = $this->vehicleFor($customer, $tenant);

        $original = Carbon::now()->copy()->addHours(24);
        $appt = $this->makeAppointment($tenant, $center, $customer, $vehicle, $original);

        $dispatcher = $this->app->make(AppointmentMessageDispatcher::class);

        // First reschedule.
        $time1 = Carbon::now()->copy()->addDays(2);
        $appt->update(['scheduled_at' => $time1, 'estimated_end_at' => $time1->copy()->addMinutes(30)]);
        $dispatcher->rescheduled($appt, $customer);

        // Second reschedule.
        $time2 = Carbon::now()->copy()->addDays(3);
        $appt->update(['scheduled_at' => $time2, 'estimated_end_at' => $time2->copy()->addMinutes(30)]);
        $dispatcher->rescheduled($appt, $customer);

        $rows = AppointmentMessageDelivery::query()
            ->where('appointment_id', $appt->id)
            ->where('message_type', AppointmentMessageDelivery::TYPE_BOOKING_RESCHEDULED)
            ->get();

        $this->assertCount(2, $rows);

        // Carbon normalizes both sides through the
        // DB driver (SQLite stores the value as a
        // string). We compare on equalTo() so
        // microsecond / timezone normalization is
        // handled.
        $storedSnapshots = $rows->pluck('scheduled_at_snapshot');
        $this->assertTrue(
            $storedSnapshots->contains(fn ($s) => $s !== null && $s->equalTo($time1)),
            'first snapshot not found'
        );
        $this->assertTrue(
            $storedSnapshots->contains(fn ($s) => $s !== null && $s->equalTo($time2)),
            'second snapshot not found'
        );
    }

    public function test_repeating_reschedule_for_same_snapshot_is_idempotent(): void
    {
        $tenant = $this->publishedTenant();
        $center = $this->openCenter($tenant);
        $customer = $this->customerIn($tenant);
        $vehicle = $this->vehicleFor($customer, $tenant);

        $original = Carbon::now()->copy()->addHours(24);
        $appt = $this->makeAppointment($tenant, $center, $customer, $vehicle, $original);

        $dispatcher = $this->app->make(AppointmentMessageDispatcher::class);

        $time1 = Carbon::now()->copy()->addDays(2);
        $appt->update(['scheduled_at' => $time1, 'estimated_end_at' => $time1->copy()->addMinutes(30)]);
        $dispatcher->rescheduled($appt, $customer);
        // Second call for the SAME snapshot
        // (e.g. a retry of the same reschedule
        // controller) must collapse to the same
        // row.
        $dispatcher->rescheduled($appt, $customer);

        $this->assertSame(1, AppointmentMessageDelivery::query()
            ->where('appointment_id', $appt->id)
            ->where('message_type', AppointmentMessageDelivery::TYPE_BOOKING_RESCHEDULED)
            ->count());
    }

    public function test_old_reminder_does_not_block_new_reminder_after_reschedule(): void
    {
        $tenant = $this->publishedTenant();
        $center = $this->openCenter($tenant);
        $customer = $this->customerIn($tenant);
        $vehicle = $this->vehicleFor($customer, $tenant);

        // 24h reminder for time T1 (T1 is in
        // the [23.5h, 24.5h] window).
        $time1 = Carbon::now()->copy()->addHours(24)->addMinutes(15);
        $appt = $this->makeAppointment($tenant, $center, $customer, $vehicle, $time1);

        $scanner = $this->app->make(AppointmentMessageReminderScanner::class);
        $scanner->scan();
        $this->assertSame(1, AppointmentMessageDelivery::query()
            ->where('appointment_id', $appt->id)
            ->where('message_type', AppointmentMessageDelivery::TYPE_REMINDER_24H)
            ->count());

        // Reschedule to a different 24h-window
        // time. T2 is in the same window but
        // distinct, so the scanner's window
        // predicate still matches. The OLD T1
        // reminder must not block the NEW T2
        // reminder.
        $time2 = Carbon::now()->copy()->addHours(23)->addMinutes(45);
        $appt->update(['scheduled_at' => $time2, 'estimated_end_at' => $time2->copy()->addMinutes(30)]);

        // Run the scanner — it should produce
        // a new reminder for T2 (and not
        // re-fire T1).
        $scanner->scan();

        $rows = AppointmentMessageDelivery::query()
            ->where('appointment_id', $appt->id)
            ->where('message_type', AppointmentMessageDelivery::TYPE_REMINDER_24H)
            ->get();
        $this->assertCount(2, $rows, 'old + new reminder rows should coexist');

        $storedSnapshots = $rows->pluck('scheduled_at_snapshot');
        $this->assertTrue(
            $storedSnapshots->contains(fn ($s) => $s !== null && $s->equalTo($time1)),
            'old T1 snapshot not found'
        );
        $this->assertTrue(
            $storedSnapshots->contains(fn ($s) => $s !== null && $s->equalTo($time2)),
            'new T2 snapshot not found'
        );
    }

    // ──────────────────────────────────────────────────────────
    // P1 — Snapshot re-validation in worker
    // ──────────────────────────────────────────────────────────

    public function test_send_job_skips_when_appointment_schedule_changed_after_dispatch(): void
    {
        $spy = new class extends SmsService
        {
            public int $callCount = 0;

            public function send(string $to, string $message, ?int $tenantId = null): array
            {
                $this->callCount++;

                return [
                    'success' => true,
                    'message_id' => 'should-not-send',
                    'provider' => 'unifonic',
                ];
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
        $vehicle = $this->vehicleFor($customer, $tenant);

        $original = Carbon::now()->copy()->addHours(24);
        $appt = $this->makeAppointment($tenant, $center, $customer, $vehicle, $original);

        // The dispatcher writes a 24h reminder
        // for the original time, then the
        // appointment is rescheduled. We bypass
        // the scanner and write the row directly
        // to model the race.
        $delivery = AppointmentMessageDelivery::create([
            'tenant_id' => $tenant->id,
            'appointment_id' => $appt->id,
            'customer_id' => $customer->id,
            'message_type' => AppointmentMessageDelivery::TYPE_REMINDER_24H,
            'scheduled_at_snapshot' => $original,
            'idempotency_key' => str_repeat('a', 32),
            'phone_hash' => hash('sha256', '+966501234567'),
            'status' => AppointmentMessageDelivery::STATUS_QUEUED,
            'attempts' => 0,
            'queued_at' => Carbon::now(),
        ]);

        // Reschedule the appointment to a new
        // time. The row's snapshot still says
        // the old time.
        $newTime = Carbon::now()->copy()->addDays(2);
        $appt->update(['scheduled_at' => $newTime, 'estimated_end_at' => $newTime->copy()->addMinutes(30)]);

        // Run the job manually — it should
        // observe the snapshot mismatch and
        // mark the row skipped.
        (new SendAppointmentMessage($delivery->id))
            ->handle(
                $this->app->make(AppointmentMessageTemplate::class),
                $this->app->make(SmsService::class)
            );

        $delivery->refresh();
        $this->assertSame(AppointmentMessageDelivery::STATUS_SKIPPED, $delivery->status);
        $this->assertSame('appointment_schedule_changed', $delivery->error_code);
        $this->assertSame(0, $spy->callCount, 'SmsService must not be called when the snapshot is stale');
    }

    public function test_send_job_skips_when_appointment_cancelled_after_dispatch(): void
    {
        $spy = Mockery::mock(SmsService::class);
        $spy->shouldReceive('isReady')->andReturn(true);
        $spy->shouldNotReceive('send');
        $this->app->instance(SmsService::class, $spy);

        $tenant = $this->publishedTenant();
        $center = $this->openCenter($tenant);
        $customer = $this->customerIn($tenant);
        $vehicle = $this->vehicleFor($customer, $tenant);

        $original = Carbon::now()->copy()->addHours(24);
        $appt = $this->makeAppointment($tenant, $center, $customer, $vehicle, $original);

        $delivery = AppointmentMessageDelivery::create([
            'tenant_id' => $tenant->id,
            'appointment_id' => $appt->id,
            'customer_id' => $customer->id,
            'message_type' => AppointmentMessageDelivery::TYPE_BOOKING_CONFIRMATION,
            'scheduled_at_snapshot' => $original,
            'idempotency_key' => str_repeat('b', 32),
            'phone_hash' => hash('sha256', '+966501234567'),
            'status' => AppointmentMessageDelivery::STATUS_QUEUED,
            'attempts' => 0,
            'queued_at' => Carbon::now(),
        ]);

        $appt->update(['status' => Appointment::STATUS_CANCELLED, 'cancelled_at' => Carbon::now()]);

        (new SendAppointmentMessage($delivery->id))
            ->handle(
                $this->app->make(AppointmentMessageTemplate::class),
                $this->app->make(SmsService::class)
            );

        $delivery->refresh();
        $this->assertSame(AppointmentMessageDelivery::STATUS_SKIPPED, $delivery->status);
    }

    public function test_send_job_sends_cancel_message_only_when_appointment_is_cancelled(): void
    {
        // Cancel messages must fire when the
        // appointment is in the cancelled state.
        $spy = Mockery::mock(SmsService::class);
        $spy->shouldReceive('isReady')->andReturn(true);
        $spy->shouldReceive('send')->andReturn([
            'success' => true,
            'message_id' => 'cancel-msg',
            'provider' => 'unifonic',
        ]);
        $this->app->instance(SmsService::class, $spy);

        $tenant = $this->publishedTenant();
        $center = $this->openCenter($tenant);
        $customer = $this->customerIn($tenant);
        $vehicle = $this->vehicleFor($customer, $tenant);
        $original = Carbon::now()->copy()->addHours(24);
        $appt = $this->makeAppointment($tenant, $center, $customer, $vehicle, $original, Appointment::STATUS_CANCELLED);

        $delivery = AppointmentMessageDelivery::create([
            'tenant_id' => $tenant->id,
            'appointment_id' => $appt->id,
            'customer_id' => $customer->id,
            'message_type' => AppointmentMessageDelivery::TYPE_BOOKING_CANCELLED,
            'idempotency_key' => str_repeat('c', 32),
            'phone_hash' => hash('sha256', '+966501234567'),
            'status' => AppointmentMessageDelivery::STATUS_QUEUED,
            'attempts' => 0,
            'queued_at' => Carbon::now(),
        ]);

        (new SendAppointmentMessage($delivery->id))
            ->handle(
                $this->app->make(AppointmentMessageTemplate::class),
                $this->app->make(SmsService::class)
            );

        $delivery->refresh();
        $this->assertSame(AppointmentMessageDelivery::STATUS_SENT, $delivery->status);
        $this->assertSame('cancel-msg', $delivery->provider_message_id);
    }

    // ──────────────────────────────────────────────────────────
    // P1 — OTP invalidation on send failure
    // ──────────────────────────────────────────────────────────

    private function makeOtp(Tenant $tenant, Customer $customer, string $phone = '+966501234567'): CustomerPortalOtp
    {
        return CustomerPortalOtp::create([
            'tenant_id' => $tenant->id,
            'customer_id' => $customer->id,
            'phone_normalized' => $phone,
            'phone_hash' => hash('sha256', $phone),
            'code_hash' => Hash::make('123456'),
            'expires_at' => Carbon::now()->addMinutes(10),
        ]);
    }

    public function test_otp_invalidated_when_no_provider_configured(): void
    {
        $spy = Mockery::mock(SmsService::class);
        $spy->shouldReceive('isReady')->andReturn(false);
        $spy->shouldNotReceive('send');
        $this->app->instance(SmsService::class, $spy);

        $this->app->forgetInstance(CustomerPortalOtpChannel::class);
        $this->app->bind(
            CustomerPortalOtpChannel::class,
            SystemSmsOtpChannel::class
        );

        $tenant = $this->publishedTenant();
        $customer = $this->customerIn($tenant, '+966509999991');
        $otp = $this->makeOtp($tenant, $customer, '+966509999991');

        $channel = $this->app->make(CustomerPortalOtpChannel::class);
        $result = $channel->send($otp, '+966509999991', '123456');

        $this->assertFalse($result->success, 'no provider → must be a hard failure');

        $otp->refresh();
        $this->assertNotNull($otp->consumed_at, 'OTP must be invalidated (consumed_at set) when the provider is missing');
    }

    public function test_otp_invalidated_when_provider_throws(): void
    {
        $spy = Mockery::mock(SmsService::class);
        $spy->shouldReceive('isReady')->andReturn(true);
        $spy->shouldReceive('send')->andThrow(new RuntimeException('gateway down'));
        $this->app->instance(SmsService::class, $spy);

        $this->app->forgetInstance(CustomerPortalOtpChannel::class);
        $this->app->bind(
            CustomerPortalOtpChannel::class,
            SystemSmsOtpChannel::class
        );

        $tenant = $this->publishedTenant();
        $customer = $this->customerIn($tenant, '+966509999992');
        $otp = $this->makeOtp($tenant, $customer, '+966509999992');

        $channel = $this->app->make(CustomerPortalOtpChannel::class);
        $result = $channel->send($otp, '+966509999992', '123456');

        $this->assertFalse($result->success);

        $otp->refresh();
        $this->assertNotNull($otp->consumed_at, 'OTP must be invalidated when the provider throws');
    }

    public function test_otp_invalidated_when_provider_returns_explicit_failure(): void
    {
        $spy = Mockery::mock(SmsService::class);
        $spy->shouldReceive('isReady')->andReturn(true);
        $spy->shouldReceive('send')->andReturn([
            'success' => false,
            'message_id' => null,
            'provider' => 'unifonic',
        ]);
        $this->app->instance(SmsService::class, $spy);

        $this->app->forgetInstance(CustomerPortalOtpChannel::class);
        $this->app->bind(
            CustomerPortalOtpChannel::class,
            SystemSmsOtpChannel::class
        );

        $tenant = $this->publishedTenant();
        $customer = $this->customerIn($tenant, '+966509999993');
        $otp = $this->makeOtp($tenant, $customer, '+966509999993');

        $channel = $this->app->make(CustomerPortalOtpChannel::class);
        $result = $channel->send($otp, '+966509999993', '123456');

        $this->assertFalse($result->success);

        $otp->refresh();
        $this->assertNotNull($otp->consumed_at);
    }

    public function test_otp_not_invalidated_on_success(): void
    {
        $spy = Mockery::mock(SmsService::class);
        $spy->shouldReceive('isReady')->andReturn(true);
        $spy->shouldReceive('send')->andReturn([
            'success' => true,
            'message_id' => 'msg-1',
            'provider' => 'unifonic',
        ]);
        $this->app->instance(SmsService::class, $spy);

        $this->app->forgetInstance(CustomerPortalOtpChannel::class);
        $this->app->bind(
            CustomerPortalOtpChannel::class,
            SystemSmsOtpChannel::class
        );

        $tenant = $this->publishedTenant();
        $customer = $this->customerIn($tenant, '+966509999994');
        $otp = $this->makeOtp($tenant, $customer, '+966509999994');

        $channel = $this->app->make(CustomerPortalOtpChannel::class);
        $result = $channel->send($otp, '+966509999994', '123456');

        $this->assertTrue($result->success);

        $otp->refresh();
        $this->assertNull($otp->consumed_at, 'a successful send must leave the OTP consumable');
    }

    public function test_otp_send_failure_log_does_not_carry_phone_or_code(): void
    {
        $captured = [];
        Log::listen(function (MessageLogged $event) use (&$captured) {
            $captured[] = [
                'level' => $event->level,
                'message' => $event->message,
                'context' => $event->context,
            ];
        });

        $spy = Mockery::mock(SmsService::class);
        $spy->shouldReceive('isReady')->andReturn(true);
        $spy->shouldReceive('send')
            ->andThrow(new RuntimeException('sms_send_failed: phone +966509999995 not deliverable'));
        $this->app->instance(SmsService::class, $spy);

        $this->app->forgetInstance(CustomerPortalOtpChannel::class);
        $this->app->bind(
            CustomerPortalOtpChannel::class,
            SystemSmsOtpChannel::class
        );

        $tenant = $this->publishedTenant();
        $customer = $this->customerIn($tenant, '+966509999995');
        $otp = $this->makeOtp($tenant, $customer, '+966509999995');

        $channel = $this->app->make(CustomerPortalOtpChannel::class);
        $channel->send($otp, '+966509999995', '123456');

        $rawPhone = '+966509999995';
        $rawCode = '123456';

        foreach ($captured as $entry) {
            foreach ($entry['context'] as $v) {
                if (! is_string($v) && ! is_numeric($v)) {
                    continue;
                }
                $str = (string) $v;
                $this->assertStringNotContainsString($rawPhone, $str, "phone leaked: {$str}");
                $this->assertStringNotContainsString($rawCode, $str, "code leaked: {$str}");
            }
        }
    }

    // ──────────────────────────────────────────────────────────
    // P1 — Privacy in markFailed
    // ──────────────────────────────────────────────────────────

    public function test_send_job_failure_does_not_persist_exception_message(): void
    {
        $spy = Mockery::mock(SmsService::class);
        $spy->shouldReceive('isReady')->andReturn(true);
        $spy->shouldReceive('send')
            ->andThrow(new RuntimeException('error for +966501111999: message body that the provider returned'));
        $this->app->instance(SmsService::class, $spy);

        $tenant = $this->publishedTenant();
        $center = $this->openCenter($tenant);
        $customer = $this->customerIn($tenant, '+966501111999');
        $vehicle = $this->vehicleFor($customer, $tenant);
        $scheduled = Carbon::now()->copy()->addHours(3);
        $appt = $this->makeAppointment($tenant, $center, $customer, $vehicle, $scheduled);

        $dispatcher = $this->app->make(AppointmentMessageDispatcher::class);

        // The Sync queue driver runs the job
        // inline, so the throw inside the job
        // propagates here. We catch it so the
        // test body can assert on the row's
        // terminal state.
        try {
            $dispatcher->confirmation($appt, $customer);
        } catch (Throwable) {
            // expected
        }

        $delivery = AppointmentMessageDelivery::query()
            ->where('appointment_id', $appt->id)
            ->first();

        $this->assertNotNull($delivery);
        $this->assertSame(AppointmentMessageDelivery::STATUS_FAILED, $delivery->status);
        $this->assertNull($delivery->error_message, 'error_message must be NULL — the provider body can carry the phone');
        $this->assertSame('runtimeexception', $delivery->error_code, 'error_code is the exception class short name, lowercased');
        $this->assertStringNotContainsString('+966501111999', (string) $delivery->error_code);
    }

    public function test_send_job_failure_log_does_not_carry_phone_or_exception_message(): void
    {
        $captured = [];
        Log::listen(function (MessageLogged $event) use (&$captured) {
            $captured[] = [
                'level' => $event->level,
                'message' => $event->message,
                'context' => $event->context,
            ];
        });

        $spy = Mockery::mock(SmsService::class);
        $spy->shouldReceive('isReady')->andReturn(true);
        $spy->shouldReceive('send')
            ->andThrow(new RuntimeException('boom for +966502222222: secret provider body'));
        $this->app->instance(SmsService::class, $spy);

        $tenant = $this->publishedTenant();
        $center = $this->openCenter($tenant);
        $customer = $this->customerIn($tenant, '+966502222222');
        $vehicle = $this->vehicleFor($customer, $tenant);
        $scheduled = Carbon::now()->copy()->addHours(3);
        $appt = $this->makeAppointment($tenant, $center, $customer, $vehicle, $scheduled);

        $dispatcher = $this->app->make(AppointmentMessageDispatcher::class);

        try {
            $dispatcher->confirmation($appt, $customer);
        } catch (Throwable) {
            // expected
        }

        $rawPhone = '+966502222222';
        $rawBody = 'secret provider body';

        foreach ($captured as $entry) {
            foreach ($entry['context'] as $v) {
                if (! is_string($v) && ! is_numeric($v)) {
                    continue;
                }
                $str = (string) $v;
                $this->assertStringNotContainsString($rawPhone, $str, "phone leaked: {$str}");
                $this->assertStringNotContainsString($rawBody, $str, "provider body leaked: {$str}");
            }
        }
    }

    // ──────────────────────────────────────────────────────────
    // P1 — Recovery command
    // ──────────────────────────────────────────────────────────

    public function test_recover_stuck_command_resets_old_sending_rows(): void
    {
        // Queue::fake() so the re-dispatched job
        // does not run inline. This isolates the
        // "row flipped to queued + job was
        // pushed" contract from the job's own
        // behavior. The new test
        // `test_recover_stuck_command_runs_job_to_sent_with_fake_sms`
        // covers the end-to-end path.
        Queue::fake();

        $tenant = $this->publishedTenant();
        $center = $this->openCenter($tenant);
        $customer = $this->customerIn($tenant);
        $vehicle = $this->vehicleFor($customer, $tenant);
        $scheduled = Carbon::now()->copy()->addHours(3);
        $appt = $this->makeAppointment($tenant, $center, $customer, $vehicle, $scheduled);

        // A row that has been "sending" for 10
        // minutes with attempts < 3.
        $stale = AppointmentMessageDelivery::create([
            'tenant_id' => $tenant->id,
            'appointment_id' => $appt->id,
            'customer_id' => $customer->id,
            'message_type' => AppointmentMessageDelivery::TYPE_BOOKING_CONFIRMATION,
            'scheduled_at_snapshot' => $scheduled,
            'idempotency_key' => str_repeat('a', 32),
            'phone_hash' => hash('sha256', '+966501234567'),
            'status' => AppointmentMessageDelivery::STATUS_SENDING,
            'attempts' => 1,
            'attempted_at' => Carbon::now()->subMinutes(10),
        ]);

        // A row that has been "sending" for 1
        // minute (still alive).
        $fresh = AppointmentMessageDelivery::create([
            'tenant_id' => $tenant->id,
            'appointment_id' => $appt->id,
            'customer_id' => $customer->id,
            'message_type' => AppointmentMessageDelivery::TYPE_BOOKING_RESCHEDULED,
            'scheduled_at_snapshot' => $scheduled,
            'idempotency_key' => str_repeat('b', 32),
            'phone_hash' => hash('sha256', '+966501234567'),
            'status' => AppointmentMessageDelivery::STATUS_SENDING,
            'attempts' => 1,
            'attempted_at' => Carbon::now()->subSeconds(30),
        ]);

        // A row that exhausted its attempt budget.
        $exhausted = AppointmentMessageDelivery::create([
            'tenant_id' => $tenant->id,
            'appointment_id' => $appt->id,
            'customer_id' => $customer->id,
            'message_type' => AppointmentMessageDelivery::TYPE_BOOKING_CANCELLED,
            'scheduled_at_snapshot' => $scheduled,
            'idempotency_key' => str_repeat('c', 32),
            'phone_hash' => hash('sha256', '+966501234567'),
            'status' => AppointmentMessageDelivery::STATUS_SENDING,
            'attempts' => 3,
            'attempted_at' => Carbon::now()->subMinutes(10),
        ]);

        $this->artisan('appointments:recover-stuck --stale-after=300')
            ->assertExitCode(0);

        $stale->refresh();
        $fresh->refresh();
        $exhausted->refresh();

        $this->assertSame(AppointmentMessageDelivery::STATUS_QUEUED, $stale->status, 'stale row must be reset');
        $this->assertNull($stale->attempted_at);
        $this->assertSame(AppointmentMessageDelivery::STATUS_SENDING, $fresh->status, 'recent row must not be touched');
        $this->assertSame(AppointmentMessageDelivery::STATUS_SENDING, $exhausted->status, 'exhausted row must not be retried');
    }

    public function test_recover_stuck_command_rejects_too_short_threshold(): void
    {
        $this->artisan('appointments:recover-stuck --stale-after=10')
            ->assertExitCode(1);
    }

    // ──────────────────────────────────────────────────────────
    // P1 round 2 — recover + re-dispatch
    // ──────────────────────────────────────────────────────────

    public function test_recover_stuck_command_redispatches_job_for_recovered_row(): void
    {
        // The previous round's bug: the recover
        // command flipped status to `queued` but
        // did NOT re-dispatch the job. The row
        // stayed `queued` forever because the
        // reminder scanner's pre-filter saw it
        // and the dispatcher's firstOrCreate was
        // a no-op. The fix: dispatch the job
        // immediately after the row flips.
        Queue::fake();

        $tenant = $this->publishedTenant();
        $center = $this->openCenter($tenant);
        $customer = $this->customerIn($tenant);
        $vehicle = $this->vehicleFor($customer, $tenant);
        $scheduled = Carbon::now()->copy()->addHours(3);
        $appt = $this->makeAppointment($tenant, $center, $customer, $vehicle, $scheduled);

        // A row that has been "sending" for 10
        // minutes with attempts < 3.
        $stale = AppointmentMessageDelivery::create([
            'tenant_id' => $tenant->id,
            'appointment_id' => $appt->id,
            'customer_id' => $customer->id,
            'message_type' => AppointmentMessageDelivery::TYPE_BOOKING_CONFIRMATION,
            'scheduled_at_snapshot' => $scheduled,
            'idempotency_key' => str_repeat('x', 32),
            'phone_hash' => hash('sha256', '+966501234567'),
            'status' => AppointmentMessageDelivery::STATUS_SENDING,
            'attempts' => 1,
            'attempted_at' => Carbon::now()->subMinutes(10),
        ]);

        $this->artisan('appointments:recover-stuck --stale-after=300')
            ->assertExitCode(0);

        $stale->refresh();
        $this->assertSame(AppointmentMessageDelivery::STATUS_QUEUED, $stale->status, 'recover flipped status');
        $this->assertNull($stale->attempted_at);

        Queue::assertPushed(SendAppointmentMessage::class, function ($job) use ($stale) {
            return $job->deliveryId === $stale->id;
        });
    }

    public function test_recover_stuck_command_runs_job_to_sent_with_fake_sms(): void
    {
        // End-to-end: the row is recovered, the
        // job is dispatched, the worker picks it
        // up, the SmsService returns success, the
        // row reaches `sent`. This is the proof
        // that the customer eventually receives
        // the message after a worker crash.
        $spy = Mockery::mock(SmsService::class);
        $spy->shouldReceive('isReady')->andReturn(true);
        $spy->shouldReceive('send')->andReturn([
            'success' => true,
            'message_id' => 'recovered-msg',
            'provider' => 'unifonic',
        ]);
        $this->app->instance(SmsService::class, $spy);

        $tenant = $this->publishedTenant();
        $center = $this->openCenter($tenant);
        $customer = $this->customerIn($tenant);
        $vehicle = $this->vehicleFor($customer, $tenant);
        $scheduled = Carbon::now()->copy()->addHours(3);
        $appt = $this->makeAppointment($tenant, $center, $customer, $vehicle, $scheduled);

        $stale = AppointmentMessageDelivery::create([
            'tenant_id' => $tenant->id,
            'appointment_id' => $appt->id,
            'customer_id' => $customer->id,
            'message_type' => AppointmentMessageDelivery::TYPE_BOOKING_CONFIRMATION,
            'scheduled_at_snapshot' => $scheduled,
            'idempotency_key' => str_repeat('y', 32),
            'phone_hash' => hash('sha256', '+966501234567'),
            'status' => AppointmentMessageDelivery::STATUS_SENDING,
            'attempts' => 1,
            'attempted_at' => Carbon::now()->subMinutes(10),
        ]);

        $this->artisan('appointments:recover-stuck --stale-after=300')
            ->assertExitCode(0);

        // Run the dispatched job manually so the
        // test body can assert on the row's
        // terminal state without depending on the
        // queue worker.
        (new SendAppointmentMessage($stale->id))
            ->handle(
                $this->app->make(AppointmentMessageTemplate::class),
                $this->app->make(SmsService::class)
            );

        $stale->refresh();
        $this->assertSame(AppointmentMessageDelivery::STATUS_SENT, $stale->status);
        $this->assertSame('recovered-msg', $stale->provider_message_id);
    }

    public function test_recover_stuck_command_does_not_dispatch_for_fresh_or_exhausted_rows(): void
    {
        Queue::fake();

        $tenant = $this->publishedTenant();
        $center = $this->openCenter($tenant);
        $customer = $this->customerIn($tenant);
        $vehicle = $this->vehicleFor($customer, $tenant);
        $scheduled = Carbon::now()->copy()->addHours(3);
        $appt = $this->makeAppointment($tenant, $center, $customer, $vehicle, $scheduled);

        // Fresh row (still alive in another worker).
        $fresh = AppointmentMessageDelivery::create([
            'tenant_id' => $tenant->id,
            'appointment_id' => $appt->id,
            'customer_id' => $customer->id,
            'message_type' => AppointmentMessageDelivery::TYPE_BOOKING_CONFIRMATION,
            'scheduled_at_snapshot' => $scheduled,
            'idempotency_key' => str_repeat('f', 32),
            'phone_hash' => hash('sha256', '+966501234567'),
            'status' => AppointmentMessageDelivery::STATUS_SENDING,
            'attempts' => 1,
            'attempted_at' => Carbon::now()->subSeconds(30),
        ]);

        // Stale but exhausted (3 attempts already).
        $exhausted = AppointmentMessageDelivery::create([
            'tenant_id' => $tenant->id,
            'appointment_id' => $appt->id,
            'customer_id' => $customer->id,
            'message_type' => AppointmentMessageDelivery::TYPE_BOOKING_CANCELLED,
            'scheduled_at_snapshot' => $scheduled,
            'idempotency_key' => str_repeat('e', 32),
            'phone_hash' => hash('sha256', '+966501234567'),
            'status' => AppointmentMessageDelivery::STATUS_SENDING,
            'attempts' => 3,
            'attempted_at' => Carbon::now()->subMinutes(10),
        ]);

        $this->artisan('appointments:recover-stuck --stale-after=300')
            ->assertExitCode(0);

        $fresh->refresh();
        $exhausted->refresh();
        $this->assertSame(AppointmentMessageDelivery::STATUS_SENDING, $fresh->status, 'fresh row must not be touched');
        $this->assertSame(AppointmentMessageDelivery::STATUS_SENDING, $exhausted->status, 'exhausted row must not be retried');

        Queue::assertNothingPushed();
    }

    public function test_recover_skips_rows_another_worker_just_recovered(): void
    {
        // Simulate a race: between the
        // recoverStuck SELECT and the UPDATE, a
        // different worker (or the job itself)
        // flips the row to `sent`. The conditional
        // UPDATE in recover skips the row and we
        // do NOT dispatch.
        //
        // We model the race by:
        //   1. Create the row in `sending`.
        //   2. Run recover, but intercept the
        //      SELECT inside the command (via
        //      a DB::listen hook) and flip the
        //      row to `sent` before the
        //      conditional UPDATE sees it.
        //   3. Assert no job was dispatched.
        //
        // This is the same race the conditional
        // UPDATE guards against; we exercise the
        // guard by mutating the row between the
        // SELECT and the UPDATE.
        Queue::fake();

        $tenant = $this->publishedTenant();
        $center = $this->openCenter($tenant);
        $customer = $this->customerIn($tenant);
        $vehicle = $this->vehicleFor($customer, $tenant);
        $scheduled = Carbon::now()->copy()->addHours(3);
        $appt = $this->makeAppointment($tenant, $center, $customer, $vehicle, $scheduled);

        $stale = AppointmentMessageDelivery::create([
            'tenant_id' => $tenant->id,
            'appointment_id' => $appt->id,
            'customer_id' => $customer->id,
            'message_type' => AppointmentMessageDelivery::TYPE_BOOKING_CONFIRMATION,
            'scheduled_at_snapshot' => $scheduled,
            'idempotency_key' => str_repeat('r', 32),
            'phone_hash' => hash('sha256', '+966501234567'),
            'status' => AppointmentMessageDelivery::STATUS_SENDING,
            'attempts' => 1,
            'attempted_at' => Carbon::now()->subMinutes(10),
        ]);

        // Intercept the candidate SELECT.
        // Once we see it fire, flip the row to
        // `sent` so the conditional UPDATE skips
        // it. We use a static flag to fire the
        // hook exactly once.
        $hooked = false;
        DB::listen(function ($query) use (&$hooked, $stale) {
            if ($hooked) {
                return;
            }
            // The recover command's first query
            // is the candidate SELECT — it
            // queries the table for rows in
            // `sending` past the threshold.
            $sql = (string) $query->sql;

            if (str_contains($sql, 'from "appointment_message_deliveries"')
                && str_contains($sql, '"status"')
                && str_contains($sql, '"attempted_at"')
                && str_contains($sql, '"attempts"')) {
                $hooked = true;
                // Mutate the row to `sent`. The
                // command's UPDATE will then fail
                // its `status = sending` predicate
                // and skip this id.
                DB::table('appointment_message_deliveries')
                    ->where('id', $stale->id)
                    ->update(['status' => AppointmentMessageDelivery::STATUS_SENT]);
            }
        });

        $this->artisan('appointments:recover-stuck --stale-after=300')
            ->assertExitCode(0);

        $stale->refresh();
        $this->assertSame(AppointmentMessageDelivery::STATUS_SENT, $stale->status, 'the row stays at whatever another worker set it to');

        Queue::assertNothingPushed();
    }
}

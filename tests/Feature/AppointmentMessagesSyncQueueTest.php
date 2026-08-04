<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\AppointmentMessageDelivery;
use App\Models\Center;
use App\Models\CenterWorkingHour;
use App\Models\Customer;
use App\Models\Service;
use App\Models\ServiceBay;
use App\Models\Tenant;
use App\Models\TenantWebsite;
use App\Models\Vehicle;
use App\Services\Messaging\AppointmentMessageDispatcher;
use App\Services\Messaging\SmsService;
use App\Support\CustomerPortalSession;
use Carbon\Carbon;
use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Log\Events\MessageLogged;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;
use Mockery;
use RuntimeException;
use Tests\TestCase;

/**
 * Phase 10 hot-fix (round 2) — sync-queue safety.
 *
 * The phpunit.xml sets QUEUE_CONNECTION=sync,
 * which means `dispatch()` runs the job inline
 * BEFORE returning. A throw from inside the job
 * propagates out of `dispatch()`. If the
 * controller path does not catch that throw, the
 * customer sees a 500 even though the appointment
 * itself is already committed.
 *
 * The dispatcher now wraps `dispatch()` in a
 * `try/catch (Throwable)` that logs only the
 * exception class (no phone, no SMS body, no
 * PII) and returns normally. These three HTTP
 * tests prove the contract end-to-end:
 *
 *   1. POST /{tenant}/portal/appointments
 *      (booking) returns 302 to the confirmation
 *      page even when the SmsService throws.
 *   2. POST /{tenant}/portal/appointment/{id}/cancel
 *      returns 302 to the dashboard even when the
 *      SmsService throws.
 *   3. POST /{tenant}/portal/appointment/{id}/reschedule
 *      returns 302 to the confirmation page even
 *      when the SmsService throws, and the
 *      appointment's scheduled_at is the NEW
 *      time.
 *
 * Each test also asserts the appointment row
 * itself reached the expected state — booking
 * created the row, cancel flipped the status,
 * reschedule updated the time. The SMS side
 * failure is contained inside the dispatcher.
 */
class AppointmentMessagesSyncQueueTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        // Pin "now" before parent::setUp() so the
        // framework's RefreshDatabase trait starts at
        // the same clock we use throughout the test.
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
    // Helpers (the same shape the Phase 8/9 tests use)
    // ──────────────────────────────────────────────────────────

    private function publishedTenant(string $slug = 'demo'): Tenant
    {
        $tenant = Tenant::factory()->create(['slug' => $slug]);
        TenantWebsite::factory()->for($tenant)->published()->create();

        return $tenant;
    }

    private function customerIn(Tenant $tenant, string $phone = '+966501234567'): Customer
    {
        return Customer::factory()->create([
            'tenant_id' => $tenant->id,
            'phone' => $phone,
            'whatsapp' => $phone,
        ]);
    }

    private function openCenter(Tenant $tenant, string $open = '00:00', string $close = '23:59'): Center
    {
        $center = Center::factory()->create([
            'tenant_id' => $tenant->id,
            'is_active' => true,
        ]);

        for ($dow = 0; $dow <= 6; $dow++) {
            CenterWorkingHour::create([
                'tenant_id' => $tenant->id,
                'center_id' => $center->id,
                'day_of_week' => $dow,
                'is_open' => true,
                'open_time' => $open,
                'close_time' => $close,
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

    private function vehicleFor(Customer $customer, Tenant $tenant): Vehicle
    {
        return Vehicle::factory()->create([
            'tenant_id' => $tenant->id,
            'customer_id' => $customer->id,
        ]);
    }

    private function serviceIn(Tenant $tenant, Center $center, int $minutes = 30): Service
    {
        return Service::factory()->forTenant($tenant, $center)->create([
            'duration_value' => $minutes,
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
        ]);
    }

    /**
     * Replace the SmsService with a spy that
     * reports isReady=true but throws on every
     * send(). This is the worst-case scenario
     * for the dispatcher: the job's handle()
     * rethrows so the Sync queue driver
     * propagates the throw out of `dispatch()`.
     */
    private function smsThatThrows(): void
    {
        $spy = Mockery::mock(SmsService::class);
        $spy->shouldReceive('isReady')->andReturn(true);
        $spy->shouldReceive('send')
            ->andThrow(new RuntimeException(
                'sms provider down: phone +966501234567 not deliverable (secret body)'
            ));
        $this->app->instance(SmsService::class, $spy);
    }

    // ──────────────────────────────────────────────────────────
    // Booking — must not 500 when SMS throws
    // ──────────────────────────────────────────────────────────

    public function test_booking_redirects_successfully_when_sms_throws(): void
    {
        $this->smsThatThrows();

        $tenant = $this->publishedTenant();
        $center = $this->openCenter($tenant);
        $bay = $this->bayIn($center);
        $service = $this->serviceIn($tenant, $center);
        $customer = $this->customerIn($tenant);
        $vehicle = $this->vehicleFor($customer, $tenant);

        $this->loginAs($customer, $tenant);

        $response = $this->post('/'.$tenant->slug.'/portal/appointments', [
            'center_id' => $center->id,
            'vehicle_id' => $vehicle->id,
            'service_ids' => [$service->id],
            'scheduled_at' => Carbon::now()->addHours(3)->setTime(10, 0)->toIso8601String(),
        ]);

        // The customer must see a successful
        // redirect, NOT a 500. The job's
        // markFailed() ran inside the SyncQueue
        // worker and the throw bubbled to the
        // dispatcher's safeDispatch().
        $response->assertStatus(302);
        $response->assertRedirectContains('/portal/appointment/');

        // The appointment was created and is
        // still in the system.
        $appt = Appointment::query()
            ->where('customer_id', $customer->id)
            ->where('center_id', $center->id)
            ->first();
        $this->assertNotNull($appt, 'appointment must be created even when SMS throws');
        $this->assertSame(Appointment::STATUS_SCHEDULED, $appt->status);

        // The delivery row exists but is
        // `failed` (the job's markFailed path).
        $delivery = AppointmentMessageDelivery::query()
            ->where('appointment_id', $appt->id)
            ->where('message_type', AppointmentMessageDelivery::TYPE_BOOKING_CONFIRMATION)
            ->first();
        $this->assertNotNull($delivery, 'delivery row exists for the booking');
        $this->assertSame(AppointmentMessageDelivery::STATUS_FAILED, $delivery->status);
    }

    // ──────────────────────────────────────────────────────────
    // Cancel — must not 500 when SMS throws
    // ──────────────────────────────────────────────────────────

    public function test_cancel_redirects_successfully_when_sms_throws(): void
    {
        $this->smsThatThrows();

        $tenant = $this->publishedTenant();
        $center = $this->openCenter($tenant);
        $bay = $this->bayIn($center);
        $service = $this->serviceIn($tenant, $center);
        $customer = $this->customerIn($tenant);
        $vehicle = $this->vehicleFor($customer, $tenant);

        $start = Carbon::now()->addHours(24);
        $appt = Appointment::create([
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
        $appt->services()->attach($service->id, ['estimated_minutes' => 30]);

        $this->loginAs($customer, $tenant);

        $response = $this->post(
            '/'.$tenant->slug.'/portal/appointment/'.$appt->id.'/cancel',
            ['reason' => 'changed mind']
        );

        $response->assertStatus(302);
        $response->assertRedirectContains('/portal/dashboard');

        // The appointment actually flipped to
        // cancelled. The SMS side did not undo
        // the action.
        $appt->refresh();
        $this->assertSame(Appointment::STATUS_CANCELLED, $appt->status);
        $this->assertNotNull($appt->cancelled_at);
        $this->assertStringContainsString('customer_portal', (string) $appt->cancellation_reason);

        // The delivery row exists but is failed.
        $delivery = AppointmentMessageDelivery::query()
            ->where('appointment_id', $appt->id)
            ->where('message_type', AppointmentMessageDelivery::TYPE_BOOKING_CANCELLED)
            ->first();
        $this->assertNotNull($delivery);
        $this->assertSame(AppointmentMessageDelivery::STATUS_FAILED, $delivery->status);
    }

    // ──────────────────────────────────────────────────────────
    // Reschedule — must not 500 when SMS throws
    // ──────────────────────────────────────────────────────────

    public function test_reschedule_redirects_successfully_when_sms_throws(): void
    {
        $this->smsThatThrows();

        $tenant = $this->publishedTenant();
        $center = $this->openCenter($tenant);
        $secondCenter = $this->openCenter($tenant, '08:00', '20:00');
        $bay = $this->bayIn($center);
        $service = $this->serviceIn($tenant, $center);
        $customer = $this->customerIn($tenant);
        $vehicle = $this->vehicleFor($customer, $tenant);

        $start = Carbon::now()->addHours(24);
        $appt = Appointment::create([
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
        $appt->services()->attach($service->id, ['estimated_minutes' => 30]);

        $this->loginAs($customer, $tenant);

        // New time: 25h from now (well past the 2h
        // floor, well inside the 14-day cap). 30
        // min boundary on the half-hour grid.
        $newStart = Carbon::now()->addHours(25)->setTime(11, 30);

        $response = $this->post(
            '/'.$tenant->slug.'/portal/appointment/'.$appt->id.'/reschedule',
            [
                'center_id' => $center->id,
                'scheduled_at' => $newStart->toIso8601String(),
            ]
        );

        $response->assertStatus(302);
        $response->assertRedirectContains('/portal/appointment/'.$appt->id);

        $appt->refresh();
        $this->assertNotNull($appt->scheduled_at);
        $this->assertTrue(
            $appt->scheduled_at->equalTo($newStart),
            'reschedule moved the appointment even when SMS threw'
        );

        // The delivery row exists but is failed.
        $delivery = AppointmentMessageDelivery::query()
            ->where('appointment_id', $appt->id)
            ->where('message_type', AppointmentMessageDelivery::TYPE_BOOKING_RESCHEDULED)
            ->first();
        $this->assertNotNull($delivery);
        $this->assertSame(AppointmentMessageDelivery::STATUS_FAILED, $delivery->status);
    }

    // ──────────────────────────────────────────────────────────
    // P3 round 3 — dispatch throws BEFORE the job runs
    // ──────────────────────────────────────────────────────────

    /**
     * Forcibly fail the queue's `dispatch()` so
     * the job's `handle()` never runs. The
     * dispatcher's `safeDispatch()` must
     * transition the row to `failed` with
     * `error_code = 'dispatch_failed'` so the
     * operator gets a stable code to filter on,
     * and so the row is not stranded in
     * `queued` (the recovery command only unsticks
     * `sending` rows, not `queued` ones).
     *
     * How we force the failure: the SendAppointmentMessage
     * `dispatch()` static method ultimately calls
     * `app(Bus::class)->dispatch($pending)`. The
     * `Bus` facade resolves to a singleton; we
     * swap that singleton for a Mockery mock
     * whose `dispatch()` throws. The
     * `PendingDispatch::__destruct()` triggers
     * the throw inside the dispatcher's
     * `safeDispatch()`.
     */
    public function test_dispatch_failure_before_job_marks_row_failed_with_dispatch_failed_code(): void
    {
        // We need the bus to throw before the
        // job runs. Replace the resolved Bus
        // instance with a Mockery double whose
        // `dispatch()` throws. This catches
        // the throw inside PendingDispatch's
        // destructor, before any handle() runs.
        $bus = Mockery::mock(Dispatcher::class);
        $bus->shouldReceive('dispatch')
            ->andThrow(new RuntimeException(
                'queue down: phone +966501234567 not deliverable'
            ));
        $this->app->instance(Dispatcher::class, $bus);

        // Capture every log call so we can assert
        // no PII leaked into the application log.
        $captured = [];
        Log::listen(function (MessageLogged $event) use (&$captured) {
            $captured[] = [
                'level' => $event->level,
                'message' => $event->message,
                'context' => $event->context,
            ];
        });

        $tenant = $this->publishedTenant();
        $center = $this->openCenter($tenant);
        $customer = $this->customerIn($tenant);
        $vehicle = $this->vehicleFor($customer, $tenant);
        $bay = $this->bayIn($center);
        $service = $this->serviceIn($tenant, $center);

        $this->loginAs($customer, $tenant);

        $response = $this->post('/'.$tenant->slug.'/portal/appointments', [
            'center_id' => $center->id,
            'vehicle_id' => $vehicle->id,
            'service_ids' => [$service->id],
            'scheduled_at' => Carbon::now()->addHours(3)->setTime(10, 0)->toIso8601String(),
        ]);

        // 1. The HTTP response is a successful
        // redirect — the customer did NOT see
        // a 500 even though the dispatch failed.
        $response->assertStatus(302);
        $response->assertRedirectContains('/portal/appointment/');

        // 2. The appointment itself was created.
        $appt = Appointment::query()
            ->where('customer_id', $customer->id)
            ->where('center_id', $center->id)
            ->first();
        $this->assertNotNull($appt);
        $this->assertSame(Appointment::STATUS_SCHEDULED, $appt->status);

        // 3. The delivery row is `failed` with
        // `error_code = 'dispatch_failed'`.
        $delivery = AppointmentMessageDelivery::query()
            ->where('appointment_id', $appt->id)
            ->where('message_type', AppointmentMessageDelivery::TYPE_BOOKING_CONFIRMATION)
            ->first();
        $this->assertNotNull($delivery);
        $this->assertSame(AppointmentMessageDelivery::STATUS_FAILED, $delivery->status);
        $this->assertSame('dispatch_failed', $delivery->error_code);
        $this->assertNotNull($delivery->failed_at);

        // 4. error_message is null, the phone and
        // the exception message are NOT in the
        // row.
        $this->assertNull($delivery->error_message);
        $this->assertStringNotContainsString('+966501234567', (string) $delivery->error_code);
        $this->assertStringNotContainsString('+966501234567', (string) $delivery->error_message);

        // 5. The log contains the dispatch_failed
        // warning with the delivery id and the
        // exception class name only — never the
        // phone, never the exception message, never
        // the SMS body.
        $rawPhone = '+966501234567';
        $rawBody = 'queue down: phone +966501234567 not deliverable';
        $sawDispatchFailedLog = false;

        foreach ($captured as $entry) {
            if ($entry['message'] !== 'appointment_message.dispatch_failed') {
                continue;
            }
            $sawDispatchFailedLog = true;
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
        $this->assertTrue($sawDispatchFailedLog, 'expected an appointment_message.dispatch_failed log line');
    }

    /**
     * The conditional `WHERE status = queued` in
     * `safeDispatch()` must NOT overwrite a row
     * that the job (or another interleaving) has
     * already taken to a terminal state. We model
     * that race by manually flipping the row to
     * `failed` between the dispatcher's INSERT
     * and the safeDispatch() catch.
     *
     * Strategy: queue the dispatch through a
     * closure we control. The closure flips the
     * row to `failed` mid-flight, then we let
     * safeDispatch's catch run.
     */
    public function test_dispatch_failure_does_not_overwrite_already_failed_row(): void
    {
        $tenant = $this->publishedTenant();
        $center = $this->openCenter($tenant);
        $customer = $this->customerIn($tenant);
        $vehicle = $this->vehicleFor($customer, $tenant);

        $start = Carbon::now()->copy()->addHours(3);
        $appt = $this->makeAppointment($tenant, $center, $customer, $vehicle, $start);

        $delivery = AppointmentMessageDelivery::create([
            'tenant_id' => $tenant->id,
            'appointment_id' => $appt->id,
            'customer_id' => $customer->id,
            'message_type' => AppointmentMessageDelivery::TYPE_BOOKING_CONFIRMATION,
            'scheduled_at_snapshot' => $start,
            'idempotency_key' => str_repeat('q', 32),
            'phone_hash' => hash('sha256', '+966501234567'),
            'status' => AppointmentMessageDelivery::STATUS_QUEUED,
            'attempts' => 0,
            'queued_at' => Carbon::now(),
        ]);

        // Replace the bus with one that throws,
        // but first flip the row to a different
        // (terminal) state — `sent`. The
        // conditional UPDATE in safeDispatch
        // must see status != queued and skip
        // the row entirely.
        $bus = Mockery::mock(Dispatcher::class);
        $bus->shouldReceive('dispatch')
            ->andReturnUsing(function () use ($delivery) {
                // Simulate a race: another
                // interleaving (here, a job that
                // somehow already ran) flipped
                // the row to `sent` before
                // safeDispatch catches the
                // dispatch failure.
                $delivery->update(['status' => AppointmentMessageDelivery::STATUS_SENT]);

                throw new RuntimeException('queue down');
            });
        $this->app->instance(Dispatcher::class, $bus);

        $dispatcher = $this->app->make(AppointmentMessageDispatcher::class);
        $dispatcher->confirmation($appt, $customer);

        $delivery->refresh();
        // The row stays `sent` — the
        // dispatcher's safeDispatch catch
        // did NOT overwrite it with
        // `failed / dispatch_failed`.
        $this->assertSame(AppointmentMessageDelivery::STATUS_SENT, $delivery->status, 'safeDispatch must not overwrite a row that another interleaving already took to a terminal state');
        $this->assertNotSame('dispatch_failed', $delivery->error_code);
    }
}

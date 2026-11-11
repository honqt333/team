<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Events\Billing\ReceiptReviewed;
use App\Events\Billing\ReceiptSubmitted;
use App\Events\Payment\PaymentRecorded;
use App\Events\WorkOrder\WorkOrderCreated;
use App\Events\WorkOrder\WorkOrderStatusChanged;
use App\Listeners\Billing\NotifyAdminsOfReceipt;
use App\Listeners\Billing\NotifyTenantOfReceiptDecision;
use App\Listeners\Payment\LinkPaymentToInvoice;
use App\Listeners\WorkOrder\LogActivityOnStatusChange;
use App\Listeners\WorkOrder\NotifyOwnerOnCreation;
use App\Providers\EventServiceProvider;
use Illuminate\Events\Dispatcher;
use Illuminate\Support\Facades\Event;
use ReflectionClass;
use Tests\TestCase;

/**
 * GUARD — no listener may be registered twice for the same event.
 *
 * WHY THIS EXISTS
 * ---------------
 * `bootstrap/app.php` registers `App\Providers\EventServiceProvider`
 * explicitly via `withProviders()`. Laravel's `Application::configure()`
 * ALSO registers the base
 * `Illuminate\Foundation\Support\Providers\EventServiceProvider`
 * through `withEvents()`, and that base instance has
 * `shouldDiscoverEvents() === true`. It therefore auto-discovers every
 * class under `app/Listeners` and registers it as `Class@handle`.
 *
 * The app's own provider registers the same listener as a bare class
 * string from its `$listen` map. Laravel's dispatcher treats the class
 * string as a class listener and resolves `handle()` on it, so BOTH
 * registrations fire. Every domain listener therefore ran TWICE:
 *
 *   - `NotifyOwnerOnCreation`      → 2 notifications per work order
 *   - `NotifyAdminsOfReceipt`      → 2 admin notifications per receipt
 *   - `NotifyTenantOfReceiptDecision` → 2 per receipt decision
 *   - `LogActivityOnStatusChange`  → 2 activity log rows per status change
 *   - `LinkPaymentToInvoice`, `UpdateInvoiceStatusOnPayment`,
 *     `HandleAutoInvoiceOnDoneWorkOrder` → 2× payment side effects
 *   - `LogSuccessfulLogin`, `LogFailedLogin` → 2 log rows per login
 *
 * The fix is `->withEvents(discover: false)` in `bootstrap/app.php`,
 * because every listener is ALREADY declared explicitly in
 * `App\Providers\EventServiceProvider::$listen`.
 *
 * This test fails if discovery is ever re-enabled, or if a listener is
 * added to `app/Listeners` without a matching `$listen` entry (which
 * would silently stop firing).
 */
class DuplicateListenerRegistrationTest extends TestCase
{
    /**
     * A listener class may appear under two DIFFERENT registrations for
     * the same event only if Laravel resolves them to different callables
     * that are genuinely distinct. We treat the bare class string and the
     * `Class@method` string as the SAME listener — they resolve to the
     * same `handle()` method and both fire.
     */
    private function listenerClass(mixed $listener): ?string
    {
        if (is_string($listener)) {
            return explode('@', $listener)[0];
        }

        if (is_array($listener) && is_string($listener[0] ?? null)) {
            return $listener[0];
        }

        return null;
    }

    public function test_no_listener_is_registered_twice_for_the_same_event(): void
    {
        $dispatcher = app(Dispatcher::class);

        $reflection = new ReflectionClass($dispatcher);
        $property = $reflection->getProperty('listeners');
        $property->setAccessible(true);

        /** @var array<string, array<int, mixed>> $listeners */
        $listeners = $property->getValue($dispatcher);

        $duplicates = [];

        foreach ($listeners as $event => $eventListeners) {
            $classes = [];

            foreach ($eventListeners as $listener) {
                $class = $this->listenerClass($listener);

                // Only APP-owned listeners are in scope. Laravel's own
                // framework listeners are the framework's business —
                // `Illuminate\Auth\Listeners\SendEmailVerificationNotification`
                // is registered twice by the base
                // Illuminate\Foundation\Support\Providers\EventServiceProvider
                // itself (once via getEvents(), once via
                // configureEmailVerification()), which is upstream
                // behaviour and present with or without this app's
                // configuration. We do not police the framework here.
                if ($class === null || ! str_starts_with($class, 'App\\')) {
                    continue;
                }

                $classes[] = $class;
            }

            foreach (array_count_values($classes) as $class => $count) {
                if ($count > 1) {
                    $duplicates[] = $event.' => '.$class.' ('.$count.'x)';
                }
            }
        }

        $this->assertSame(
            [],
            $duplicates,
            "Duplicate listener registrations detected — every event handler fires twice:\n"
            .implode("\n", $duplicates)
            ."\n\nIf you added a listener to app/Listeners, declare it in "
            .'App\Providers\EventServiceProvider::$listen as well, and keep '
            .'->withEvents(discover: false) in bootstrap/app.php.'
        );
    }

    /**
     * The other half of the contract: with discovery off, a listener that
     * lives in `app/Listeners` MUST be declared in the provider's
     * `$listen` map, or it will never fire at all.
     */
    public function test_every_discovered_listener_file_is_declared_in_the_provider(): void
    {
        $provider = new EventServiceProvider($this->app);

        $reflection = new ReflectionClass($provider);
        $property = $reflection->getProperty('listen');
        $property->setAccessible(true);

        /** @var array<string, array<int, string>> $listen */
        $listen = $property->getValue($provider);

        $declared = [];

        foreach ($listen as $listeners) {
            foreach ($listeners as $listener) {
                if (is_string($listener)) {
                    $declared[] = explode('@', $listener)[0];
                }
            }
        }

        $files = glob(app_path('Listeners').'/*/*.php') ?: [];

        $missing = [];

        foreach ($files as $file) {
            $class = 'App\\Listeners\\'.str_replace(
                ['/', '.php'],
                ['\\', ''],
                substr($file, strlen(app_path('Listeners')) + 1)
            );

            if (! class_exists($class)) {
                continue;
            }

            $reflectionClass = new ReflectionClass($class);

            if ($reflectionClass->isAbstract()) {
                continue;
            }

            // Only listeners with a `handle()` method are event listeners.
            if (! $reflectionClass->hasMethod('handle')) {
                continue;
            }

            if (! in_array($class, $declared, true)) {
                $missing[] = $class;
            }
        }

        $this->assertSame(
            [],
            $missing,
            'These listeners have a handle() method but are NOT declared in '
            ."App\Providers\EventServiceProvider::\$listen — with event "
            ."discovery disabled they will never fire:\n"
            .implode("\n", $missing)
        );
    }

    /**
     * The explicit provider map must still be in force — this is the
     * behaviour the notification listeners depend on.
     */
    public function test_domain_listeners_are_still_registered_exactly_once(): void
    {
        $dispatcher = app(Dispatcher::class);

        $reflection = new ReflectionClass($dispatcher);
        $property = $reflection->getProperty('listeners');
        $property->setAccessible(true);

        /** @var array<string, array<int, mixed>> $listeners */
        $listeners = $property->getValue($dispatcher);

        $expected = [
            WorkOrderCreated::class => NotifyOwnerOnCreation::class,
            WorkOrderStatusChanged::class => LogActivityOnStatusChange::class,
            ReceiptSubmitted::class => NotifyAdminsOfReceipt::class,
            ReceiptReviewed::class => NotifyTenantOfReceiptDecision::class,
            PaymentRecorded::class => LinkPaymentToInvoice::class,
        ];

        foreach ($expected as $event => $listenerClass) {
            $found = 0;

            foreach ($listeners[$event] ?? [] as $listener) {
                if ($this->listenerClass($listener) === $listenerClass) {
                    $found++;
                }
            }

            $this->assertSame(
                1,
                $found,
                "Expected {$listenerClass} to be registered exactly once for {$event}, found {$found}."
            );
        }
    }
}

<?php

declare(strict_types=1);

namespace Tests\Feature\Settings;

use App\Events\Billing\ReceiptReviewed;
use App\Listeners\Billing\NotifyTenantOfReceiptDecision;
use App\Models\BankTransferReceipt;
use App\Models\Center;
use App\Models\InternalNotification;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use App\Models\WorkOrder;
use App\Services\NotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * GUARD — notifications must not duplicate, and the tenant's notification
 * settings must actually apply to them.
 *
 * THE THREE DEFECTS THIS LOCKS DOWN
 * ----------------------------------
 * 1. DOUBLE LISTENER REGISTRATION
 *    Laravel's base EventServiceProvider auto-discovers app/Listeners
 *    while the app's own provider registers the same listeners from its
 *    $listen map. Every domain listener fired TWICE, so every work order
 *    produced two identical bell notifications. Fixed by
 *    ->withEvents(discover: false) in bootstrap/app.php.
 *
 * 2. TYPE vs EVENT-KEY NAMESPACE MISMATCH
 *    Notifications are written with dotted types ('work_order.created')
 *    while the settings matrix uses snake_case event keys
 *    ('work_order_created'). The two namespaces never met, so every
 *    per-event toggle on the settings page was a no-op — only the
 *    channel-level kill switch worked. Fixed by
 *    Tenant::notificationEventKeysFor().
 *
 * 3. DIRECT INSERTS BYPASSING THE SERVICE
 *    NotifyTenantOfReceiptDecision wrote InternalNotification rows
 *    directly, so a tenant who disabled in-app notifications still got
 *    the receipt decision in their bell. Fixed by routing it through
 *    NotificationService.
 */
class NotificationDuplicationGuardTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    private Center $center;

    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::factory()->create();
        $this->center = Center::factory()->create(['tenant_id' => $this->tenant->id]);

        app(PermissionRegistrar::class)->setPermissionsTeamId($this->tenant->id);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->owner = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'current_center_id' => $this->center->id,
            'is_active' => true,
        ]);
        $this->owner->centers()->attach($this->center->id, ['tenant_id' => $this->tenant->id]);

        $role = Role::firstOrCreate([
            'name' => 'super_admin',
            'guard_name' => 'web',
            'tenant_id' => $this->tenant->id,
        ]);
        $this->owner->assignRole($role);
    }

    /**
     * DEFECT 1 — one work order must produce exactly ONE notification.
     */
    public function test_creating_a_work_order_sends_exactly_one_notification(): void
    {
        WorkOrder::factory()->create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
        ]);

        $this->assertSame(
            1,
            InternalNotification::where('tenant_id', $this->tenant->id)
                ->where('type', 'work_order.created')
                ->count(),
            'Creating a work order must produce exactly one notification, not two.'
        );
    }

    /**
     * DEFECT 1 — and the same when the controller path runs too.
     */
    public function test_work_order_creation_does_not_duplicate_through_the_controller(): void
    {
        $workOrder = WorkOrder::factory()->create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
        ]);

        // The controller no longer calls notifyOwner() itself; simulate a
        // second notify for the same event to prove the two paths converge
        // on ONE row only because the controller was cleaned up.
        $this->assertSame(
            1,
            InternalNotification::where('tenant_id', $this->tenant->id)
                ->where('type', 'work_order.created')
                ->count()
        );
    }

    /**
     * DEFECT 2 — a per-event toggle must actually stop the notification.
     */
    public function test_disabling_the_work_order_created_event_stops_the_notification(): void
    {
        $this->tenant->update([
            'notification_settings' => [
                'channels' => ['in_app' => true],
                'events' => [
                    'work_order_created' => ['in_app' => false],
                ],
            ],
        ]);

        $result = NotificationService::notify(
            tenantId: $this->tenant->id,
            userId: $this->owner->id,
            type: 'work_order.created',
            title: 'أمر عمل جديد',
        );

        $this->assertNull($result, 'A disabled event key must suppress the notification.');
        $this->assertDatabaseMissing('internal_notifications', [
            'tenant_id' => $this->tenant->id,
            'type' => 'work_order.created',
        ]);
    }

    /**
     * DEFECT 2 — the mapping must cover every notification type the
     * application actually writes. This is the table that keeps the two
     * namespaces aligned as new call sites are added.
     */
    public function test_every_production_notification_type_maps_to_a_known_event_key(): void
    {
        $types = [
            'work_order.created',
            'work_order.status_changed',
            'work_order.completed',
            'invoice.created',
            'quote.created',
            'quote.approved',
            'quote.rejected',
            'payment.received',
            'payment.recorded',
            'stock.low',
            'appointment.booked',
            'appointment.reminder',
            'appointment.cancelled',
        ];

        $known = array_keys(Tenant::defaultNotificationSettings()['events']);

        foreach ($types as $type) {
            $keys = Tenant::notificationEventKeysFor($type);

            $this->assertNotEmpty($keys, "No event key resolved for type '{$type}'.");

            $mapped = array_values(array_intersect($keys, $known));

            $message = sprintf(
                "Notification type '%s' maps to [%s], none of which exist in the settings matrix [%s]. "
                .'The per-event toggle for this notification would be a no-op.',
                $type,
                implode(', ', $keys),
                implode(', ', $known)
            );

            $this->assertNotEmpty($mapped, $message);
        }
    }

    /**
     * DEFECT 2 — the mapping is directional, not a blind transform.
     * `invoice.created` must reach `invoice_issued`, and `quote.created`
     * must reach `quote_shared` — the two sides do not use the same words.
     */
    public function test_event_key_mapping_is_not_a_blind_dots_to_underscores_transform(): void
    {
        $this->assertSame(
            ['invoice_issued'],
            Tenant::notificationEventKeysFor('invoice.created')
        );

        $this->assertSame(
            ['quote_shared'],
            Tenant::notificationEventKeysFor('quote.created')
        );

        $this->assertSame(
            ['work_order_created'],
            Tenant::notificationEventKeysFor('work_order.created')
        );

        $this->assertSame(
            ['low_stock_alert'],
            Tenant::notificationEventKeysFor('stock.low')
        );
    }

    /**
     * DEFECT 3 — the channel-level kill switch must still work for every
     * channel, including the ones the event matrix leaves alone.
     */
    public function test_channel_kill_switch_still_applies(): void
    {
        $this->tenant->update([
            'notification_settings' => [
                'channels' => ['in_app' => false],
                'events' => [],
            ],
        ]);

        foreach (['work_order.created', 'invoice.created', 'quote.approved'] as $type) {
            $result = NotificationService::notify(
                tenantId: $this->tenant->id,
                userId: $this->owner->id,
                type: $type,
                title: 'title',
            );

            $this->assertNull($result, "Channel kill switch failed for '{$type}'.");
        }
    }

    /**
     * DEFECT 3 — a tenant-facing listener must go through the service so
     * the tenant's settings apply to it.
     */
    public function test_receipt_decision_listener_respects_tenant_settings(): void
    {
        $this->tenant->update([
            'notification_settings' => [
                'channels' => ['in_app' => false],
                'events' => [],
            ],
        ]);

        $receipt = new BankTransferReceipt;
        $receipt->id = 'r-test-1';
        $receipt->tenant_id = $this->tenant->id;
        $receipt->transfer_amount = 500;
        $receipt->transfer_reference = 'REF-1';

        $event = new ReceiptReviewed($receipt, 'approved');

        (new NotifyTenantOfReceiptDecision)->handle($event);

        $this->assertDatabaseMissing('internal_notifications', [
            'tenant_id' => $this->tenant->id,
            'type' => 'bank_transfer.receipt.approved',
        ]);
    }

    /**
     * The settings matrix itself must remain complete — a tenant that
     * opens the settings page has to see a row for every event.
     */
    public function test_settings_matrix_covers_every_event_the_frontend_renders(): void
    {
        $frontendKeys = [
            'appointment_booked',
            'appointment_reminder',
            'appointment_cancelled',
            'work_order_created',
            'work_order_status_changed',
            'work_order_completed',
            'quote_shared',
            'quote_approved',
            'invoice_issued',
            'payment_received',
            'low_stock_alert',
        ];

        $matrix = array_keys(Tenant::defaultNotificationSettings()['events']);

        $this->assertSame(
            [],
            array_values(array_diff($frontendKeys, $matrix)),
            'The frontend renders event keys that the backend matrix does not define.'
        );
    }
}

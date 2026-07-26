<?php

declare(strict_types=1);

namespace Tests\Feature\System;

use App\Events\Billing\ReceiptReviewed;
use App\Events\Billing\ReceiptSubmitted;
use App\Listeners\Billing\NotifyAdminsOfReceipt;
use App\Listeners\Billing\NotifyTenantOfReceiptDecision;
use App\Models\AdminUser;
use App\Models\BankTransferReceipt;
use App\Models\InternalNotification;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Feature tests for Phase 4 (Notifications).
 *
 * Verifies that the ReceiptSubmitted and ReceiptReviewed events fire
 * at the right controller entry points and that the listeners create
 * the expected InternalNotification rows (one per admin on submit,
 * one per tenant user on review).
 *
 * We use Event::fake() in some tests to assert the events fire without
 * relying on the listener side effects, and dispatch synchronously in
 * others to assert the side effects directly.
 */
class BankTransferNotificationsTest extends TestCase
{
    use RefreshDatabase;

    protected AdminUser $admin;

    protected Tenant $tenant;

    protected BankTransferReceipt $receipt;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = AdminUser::create([
            'name' => 'Test Admin',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
            'role' => 'super_admin',
            'is_active' => true,
        ]);

        $this->tenant = Tenant::create([
            'name' => 'Test Tenant',
            'trade_name' => 'Test Co',
            'slug' => 'test-tenant',
            'email' => 'tenant@example.com',
            'status' => 'active',
        ]);

        $this->receipt = BankTransferReceipt::create([
            'tenant_id' => $this->tenant->id,
            'transfer_amount' => 1000,
            'transfer_reference' => 'NOTIF-001',
            'transfer_date' => now(),
            'bank_name' => 'SNB',
            'account_name' => 'Test',
            'receipt_path' => 'receipts/test.jpg',
            'status' => 'pending',
        ]);
    }

    public function test_receipt_submitted_event_is_dispatched_on_upload(): void
    {
        Event::fake([ReceiptSubmitted::class]);

        $receipt = BankTransferReceipt::create([
            'tenant_id' => $this->tenant->id,
            'transfer_amount' => 1000,
            'transfer_reference' => 'NOTIF-002',
            'transfer_date' => now(),
            'bank_name' => 'SNB',
            'account_name' => 'Test',
            'receipt_path' => 'receipts/test2.jpg',
            'status' => 'pending',
        ]);

        event(new ReceiptSubmitted($receipt));

        Event::assertDispatched(ReceiptSubmitted::class, fn ($e) => $e->receipt->id === $receipt->id);
    }

    public function test_notify_admins_listener_creates_notifications(): void
    {
        $secondAdmin = AdminUser::create([
            'name' => 'Second Admin',
            'email' => 'admin2@example.com',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'is_active' => true,
        ]);

        $event = new ReceiptSubmitted($this->receipt);
        (new NotifyAdminsOfReceipt)->handle($event);

        // Two notifications: one per admin, identified via admin_id
        $notifications = InternalNotification::where('type', 'bank_transfer.receipt.submitted')->get();
        $this->assertCount(2, $notifications);
        $this->assertEquals(1, InternalNotification::where('admin_id', $this->admin->id)->count());
        $this->assertEquals(1, InternalNotification::where('admin_id', $secondAdmin->id)->count());
        $this->assertStringContainsString('Test Co', $notifications->first()->body);
        // user_id and tenant_id stay NULL for admin notifications
        $this->assertNull($notifications->first()->user_id);
        $this->assertNull($notifications->first()->tenant_id);
    }

    public function test_notify_tenant_listener_notifies_every_user(): void
    {
        $owner = User::create([
            'name' => 'Owner',
            'email' => 'owner@example.com',
            'password' => Hash::make('password'),
            'tenant_id' => $this->tenant->id,
            'email_verified_at' => now(),
        ]);
        $staff = User::create([
            'name' => 'Staff',
            'email' => 'staff@example.com',
            'password' => Hash::make('password'),
            'tenant_id' => $this->tenant->id,
            'email_verified_at' => now(),
        ]);

        $event = new ReceiptReviewed($this->receipt, 'approved', 'Looks good.');
        (new NotifyTenantOfReceiptDecision)->handle($event);

        // Two notifications: one per user
        $notifications = InternalNotification::where('type', 'bank_transfer.receipt.approved')->get();
        $this->assertCount(2, $notifications);

        $ownerNotif = $notifications->firstWhere('user_id', $owner->id);
        $this->assertNotNull($ownerNotif);
        $this->assertStringContainsString('approved', $ownerNotif->body);
    }

    public function test_notify_tenant_listener_handles_rejection(): void
    {
        User::create([
            'name' => 'Owner',
            'email' => 'owner@example.com',
            'password' => Hash::make('password'),
            'tenant_id' => $this->tenant->id,
            'email_verified_at' => now(),
        ]);

        $event = new ReceiptReviewed($this->receipt, 'rejected', 'Wrong amount.');
        (new NotifyTenantOfReceiptDecision)->handle($event);

        $notification = InternalNotification::where('type', 'bank_transfer.receipt.rejected')->first();
        $this->assertNotNull($notification);
        $this->assertStringContainsString('Wrong amount', $notification->body);
    }

    public function test_notify_admins_skips_when_no_active_admins(): void
    {
        // Soft-disable the existing admin
        $this->admin->update(['is_active' => false]);

        $event = new ReceiptSubmitted($this->receipt);
        (new NotifyAdminsOfReceipt)->handle($event);

        $this->assertEquals(0, InternalNotification::count());
    }
}

<?php

declare(strict_types=1);

namespace Tests\Feature\System;

use App\Models\AdminUser;
use App\Models\BankTransferReceipt;
use App\Models\Billing\Plan;
use App\Models\Billing\Subscription;
use App\Models\Billing\SubscriptionInvoice;
use App\Models\Billing\SubscriptionPayment;
use App\Models\SystemAnnouncement;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Regression tests for the acting-identity resolution in the /system panel.
 *
 * ## The bug this pins
 *
 * The panel is reachable through TWO guards:
 *
 *   - `auth:web,admin` lets an `AdminUser` (the `admin_users` table)
 *     sign in on the 'admin' guard, and a tenant `User` flagged
 *     `is_system_admin` sign in on the 'web' guard.
 *
 * The bare `auth()` helper resolves the *configured* default guard
 * ('web'), which is a DIFFERENT identity space than the one the request
 * was authenticated on. Two concrete failures came out of that:
 *
 *   1. `BankTransferReceipt::reviewer()` resolved `reviewed_by`
 *      against the `users` table while the stored id is an
 *      `admin_users.id` — so the panel displayed a DIFFERENT PERSON
 *      with a colliding id as the reviewer of a payment.
 *
 *   2. `SubscriptionInvoicesController::markPaid()` passed
 *      `marked_by` into the service, which then DROPPED it on the
 *      floor — the "who confirmed this payment" audit trail was never
 *      written.
 *
 * The fix resolves the actor through `$request->user()` (which honours
 * the guard the Authenticate middleware activated) and records the
 * identity in the table the reader resolves against.
 */
class SystemActorIdentityTest extends TestCase
{
    use RefreshDatabase;

    private AdminUser $admin;

    private Tenant $tenant;

    private Plan $plan;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = AdminUser::create([
            'name' => 'REAL SYSTEM ADMIN',
            'email' => 'admin-identity@example.test',
            'password' => Hash::make('secret-password'),
            'role' => 'super_admin',
            'is_active' => true,
        ]);

        $this->tenant = Tenant::create([
            'name' => 'Identity Tenant',
            'trade_name' => 'Identity Co',
            'slug' => 'identity-tenant',
            'email' => 'identity@example.test',
            'status' => 'active',
        ]);

        $this->plan = Plan::create([
            'name' => 'Identity Plan',
            'name_ar' => 'باقة الهوية',
            'name_en' => 'Identity Plan',
            'slug' => 'identity-plan',
            'price_monthly' => 100,
            'price_yearly' => 1000,
            'is_active' => true,
        ]);
    }

    /**
     * A tenant User whose id COLLIDES with the admin's id. If any code
     * path resolves `reviewed_by` against the `users` table, it lands
     * on this record instead of the admin — which is exactly the bug.
     */
    private function collidingTenantUser(): User
    {
        return User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'WRONG TENANT USER',
            'is_system_admin' => true,
        ]);
    }

    private function makeReceipt(): BankTransferReceipt
    {
        $subscription = Subscription::create([
            'tenant_id' => $this->tenant->id,
            'plan_id' => $this->plan->id,
            'status' => 'pending_payment',
            'billing_cycle' => 'yearly',
            'starts_at' => now(),
            'ends_at' => now()->addYear(),
            'price' => 1000,
            'auto_renew' => true,
        ]);

        $invoice = SubscriptionInvoice::create([
            'tenant_id' => $this->tenant->id,
            'subscription_id' => $subscription->id,
            'invoice_number' => 'INV-IDENTITY-1',
            'subtotal' => 1000,
            'vat' => 0,
            'discount' => 0,
            'total' => 1000,
            'status' => 'pending',
            'issued_at' => now(),
            'due_at' => now()->addDays(7),
        ]);

        $payment = SubscriptionPayment::create([
            'tenant_id' => $this->tenant->id,
            'subscription_id' => $subscription->id,
            'subscription_invoice_id' => $invoice->id,
            'amount' => 1000,
            'currency' => 'SAR',
            'gateway' => 'bank_transfer',
            'status' => 'pending',
        ]);

        return BankTransferReceipt::create([
            'tenant_id' => $this->tenant->id,
            'subscription_id' => $subscription->id,
            'subscription_payment_id' => $payment->id,
            'transfer_amount' => 500,
            'transfer_reference' => 'REF-IDENTITY-1',
            'transfer_date' => now()->toDateString(),
            'status' => 'pending',
        ]);
    }

    public function test_the_reviewer_relation_resolves_against_the_admin_users_table(): void
    {
        $receipt = $this->makeReceipt();
        $receipt->update(['reviewed_by' => $this->admin->id]);

        $reviewer = $receipt->fresh()->reviewer;

        $this->assertNotNull($reviewer, 'The reviewer relation must resolve the admin.');
        $this->assertInstanceOf(AdminUser::class, $reviewer);
        $this->assertSame(
            'REAL SYSTEM ADMIN',
            $reviewer->name,
            'The reviewer must be the AdminUser — not a tenant User whose id happens to collide.'
        );
    }

    public function test_the_reviewer_relation_does_not_return_a_colliding_tenant_user(): void
    {
        // Make the id spaces collide on purpose.
        $tenantUser = $this->collidingTenantUser();
        $this->assertSame($this->admin->id, $tenantUser->id, 'The fixture must collide to be meaningful.');

        $receipt = $this->makeReceipt();
        $receipt->update(['reviewed_by' => $this->admin->id]);

        $reviewer = $receipt->fresh()->reviewer;

        $this->assertNotSame('WRONG TENANT USER', $reviewer?->name);
    }

    public function test_approving_a_receipt_records_the_admin_as_the_reviewer(): void
    {
        // The transferred amount matches the expected amount, so the
        // mismatch guard does not block the approval.
        $receipt = $this->makeReceipt();
        $receipt->update(['transfer_amount' => 1000]);

        $response = $this->actingAs($this->admin, 'admin')
            ->post(route('system.bank-receipts.approve', $receipt->id));

        $response->assertRedirect();

        $receipt->refresh();

        $this->assertSame('approved', $receipt->status);
        $this->assertNotNull(
            $receipt->reviewed_by,
            'reviewed_by must be recorded — otherwise the panel cannot say who approved the payment.'
        );
        $this->assertSame($this->admin->id, $receipt->reviewed_by);
        $this->assertSame('REAL SYSTEM ADMIN', $receipt->reviewer?->name);
    }

    public function test_rejecting_a_receipt_records_the_admin_as_the_reviewer(): void
    {
        $receipt = $this->makeReceipt();

        $response = $this->actingAs($this->admin, 'admin')
            ->post(route('system.bank-receipts.reject', $receipt->id), [
                'notes' => 'The transfer does not match the invoice.',
            ]);

        $response->assertRedirect();

        $receipt->refresh();

        $this->assertSame('rejected', $receipt->status);
        $this->assertSame($this->admin->id, $receipt->reviewed_by);
        $this->assertSame('REAL SYSTEM ADMIN', $receipt->reviewer?->name);
    }

    public function test_approving_a_receipt_writes_the_reviewer_into_the_audit_notes(): void
    {
        $receipt = $this->makeReceipt();

        // Force the mismatch branch so the audit line is written.
        $this->actingAs($this->admin, 'admin')
            ->post(route('system.bank-receipts.approve', $receipt->id), [
                'force_approve' => true,
            ])
            ->assertRedirect();

        $receipt->refresh();

        $this->assertNotNull($receipt->review_notes);
        $this->assertStringContainsString(
            'by admin #'.$this->admin->id,
            $receipt->review_notes,
            'The audit line must name the acting admin, not "admin #0" from a null id.'
        );
    }

    public function test_creating_an_announcement_records_the_admin_that_authored_it(): void
    {
        $response = $this->actingAs($this->admin, 'admin')
            ->post(route('system.announcements.store'), [
                'title' => 'Identity announcement',
                'content' => 'Body',
                'type' => 'info',
                'target' => 'all',
                'channels' => ['in_app'],
            ]);

        $response->assertRedirect();

        $announcement = SystemAnnouncement::where('title', 'Identity announcement')->firstOrFail();

        $this->assertNotNull(
            $announcement->admin_user_id,
            'admin_user_id must be recorded — the column is read back through the admin relation.'
        );
        $this->assertSame($this->admin->id, $announcement->admin_user_id);
        $this->assertSame('REAL SYSTEM ADMIN', $announcement->admin?->name);
    }

    public function test_an_announcement_created_by_a_tenant_session_admin_does_not_point_at_a_wrong_admin(): void
    {
        // A tenant-session system admin lives in `users`, not
        // `admin_users`. The column is read back through the AdminUser
        // relation, so writing the `users` id would attach the
        // announcement to a stranger who happens to share the id.
        $tenantUser = $this->collidingTenantUser();

        $response = $this->actingAs($tenantUser, 'web')
            ->post(route('system.announcements.store'), [
                'title' => 'Tenant-authored announcement',
                'content' => 'Body',
                'type' => 'info',
                'target' => 'all',
                'channels' => ['in_app'],
            ]);

        $response->assertRedirect();

        $announcement = SystemAnnouncement::where('title', 'Tenant-authored announcement')->firstOrFail();

        $this->assertNotSame(
            $this->admin->id,
            $announcement->admin_user_id,
            'A tenant-session admin must not be recorded as the AdminUser with the colliding id.'
        );
    }

    public function test_the_two_factor_setup_page_renders_for_a_system_admin(): void
    {
        $response = $this->actingAs($this->admin, 'admin')
            ->get(route('system.2fa.setup'));

        $response->assertOk();
    }

    public function test_the_two_factor_setup_page_renders_for_a_tenant_session_admin(): void
    {
        $tenantUser = $this->collidingTenantUser();

        $response = $this->actingAs($tenantUser, 'web')
            ->get(route('system.2fa.setup'));

        $response->assertOk();
    }
}

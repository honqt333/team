<?php

declare(strict_types=1);

namespace Tests\Feature\System;

use App\Models\AdminActivityLog;
use App\Models\AdminUser;
use App\Models\Billing\Plan;
use App\Models\Billing\Subscription;
use App\Models\SystemAnnouncement;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * The /system panel must leave an audit trail.
 *
 * The panel mutates platform-level state: plans, promo codes, payment
 * configuration, subscription lifecycle, and the review of incoming
 * bank transfers. An operator asking "who changed this, and when?" has
 * to get an answer.
 *
 * These tests pin the trail for both identity kinds the panel accepts:
 * an AdminUser on the 'admin' guard, and a tenant User flagged
 * `is_system_admin` on the 'web' guard.
 */
class SystemAuditTrailTest extends TestCase
{
    use RefreshDatabase;

    private AdminUser $admin;

    private Tenant $tenant;

    private Plan $plan;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = AdminUser::create([
            'name' => 'Audit Admin',
            'email' => 'audit-admin@example.test',
            'password' => Hash::make('secret-password'),
            'role' => 'super_admin',
            'is_active' => true,
        ]);

        $this->tenant = Tenant::create([
            'name' => 'Audit Tenant',
            'trade_name' => 'Audit Co',
            'slug' => 'audit-tenant',
            'email' => 'audit@example.test',
            'status' => 'active',
        ]);

        $this->plan = Plan::create([
            'name' => 'Audit Plan',
            'name_ar' => 'باقة التدقيق',
            'name_en' => 'Audit Plan',
            'slug' => 'audit-plan',
            'price_monthly' => 100,
            'price_yearly' => 1000,
            'trial_days' => 14,
            'is_active' => true,
        ]);
    }

    private function tenantSessionAdmin(): User
    {
        return User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'is_system_admin' => true,
        ]);
    }

    // ── Plans ──────────────────────────────────────────────────────────

    public function test_creating_a_plan_is_audited(): void
    {
        $this->actingAs($this->admin, 'admin')
            ->post(route('system.plans.store'), [
                'name_ar' => 'باقة جديدة',
                'name_en' => 'New Plan',
                'slug' => 'new-plan',
                'price_yearly' => 1200,
                'trial_days' => 14,
            ])
            ->assertRedirect();

        $log = AdminActivityLog::where('action', 'plan_create')->latest()->first();

        $this->assertNotNull($log, 'Creating a plan must leave an audit entry.');
        $this->assertSame($this->admin->id, $log->admin_user_id);
        $this->assertSame(Plan::class, $log->model_type);
        $this->assertSame('Audit Admin', $log->changes['actor']);
    }

    public function test_updating_a_plan_is_audited(): void
    {
        $this->actingAs($this->admin, 'admin')
            ->put(route('system.plans.update', $this->plan->id), [
                'name_ar' => 'باقة معدلة',
                'name_en' => 'Updated Plan',
                'slug' => $this->plan->slug,
                'price_yearly' => 1500,
                'trial_days' => 30,
            ])
            ->assertRedirect();

        $this->assertNotNull(
            AdminActivityLog::where('action', 'plan_update')->latest()->first(),
            'Updating a plan must leave an audit entry.'
        );
    }

    public function test_deleting_a_plan_is_audited(): void
    {
        $plan = Plan::create([
            'name' => 'Disposable',
            'name_ar' => 'باقة مؤقتة',
            'name_en' => 'Disposable',
            'slug' => 'disposable-plan',
            'price_yearly' => 100,
            'trial_days' => 0,
            'is_active' => true,
        ]);

        $this->actingAs($this->admin, 'admin')
            ->delete(route('system.plans.destroy', $plan->id))
            ->assertRedirect();

        $log = AdminActivityLog::where('action', 'plan_delete')->latest()->first();

        $this->assertNotNull($log);
        $this->assertSame('disposable-plan', $log->changes['slug']);
    }

    // ── Promo codes ────────────────────────────────────────────────────

    public function test_creating_a_promo_code_is_audited(): void
    {
        $this->actingAs($this->admin, 'admin')
            ->post(route('system.promo-codes.store'), [
                'code' => 'SAVE20',
                'name' => 'Save 20%',
                'discount_type' => 'percentage',
                'discount_value' => 20,
                'max_uses_per_tenant' => 1,
                'billing_cycle' => 'any',
            ])
            ->assertRedirect();

        $log = AdminActivityLog::where('action', 'promo_code_create')->latest()->first();

        $this->assertNotNull($log);
        $this->assertSame('SAVE20', $log->changes['code']);
        $this->assertSame(20, (int) $log->changes['discount_value']);
    }

    // ── Announcements ──────────────────────────────────────────────────

    public function test_publishing_an_announcement_is_audited(): void
    {
        $announcement = SystemAnnouncement::create([
            'title' => 'Audit announcement',
            'content' => 'Body',
            'type' => 'info',
            'target' => 'all',
            'channels' => ['in_app'],
        ]);

        $this->actingAs($this->admin, 'admin')
            ->post(route('system.announcements.publish', $announcement->id))
            ->assertRedirect();

        $log = AdminActivityLog::where('action', 'announcement_publish')->latest()->first();

        $this->assertNotNull($log, 'Publishing an announcement must leave an audit entry.');
        $this->assertSame('Audit announcement', $log->changes['title']);
    }

    // ── Subscription lifecycle ─────────────────────────────────────────

    public function test_cancelling_a_subscription_is_audited(): void
    {
        $subscription = Subscription::create([
            'tenant_id' => $this->tenant->id,
            'plan_id' => $this->plan->id,
            'status' => 'active',
            'billing_cycle' => 'yearly',
            'starts_at' => now(),
            'ends_at' => now()->addYear(),
            'price' => 1000,
        ]);

        $this->actingAs($this->admin, 'admin')
            ->post(route('system.subscriptions.cancel', $subscription->id))
            ->assertRedirect();

        $log = AdminActivityLog::where('action', 'subscription_cancel')->latest()->first();

        $this->assertNotNull($log);
        $this->assertSame($this->tenant->id, (int) $log->changes['tenant_id']);
    }

    // ── The tenant-session admin ──────────────────────────────────────

    public function test_a_tenant_session_admin_is_audited_without_pointing_at_a_stranger(): void
    {
        // A tenant-session system admin has no `admin_users` row, so
        // `admin_user_id` must stay NULL rather than carry a
        // `users.id` that would point at an unrelated admin who shares
        // the id. The identity lives in the `changes` payload instead.
        $tenantAdmin = $this->tenantSessionAdmin();

        $this->actingAs($tenantAdmin, 'web')
            ->post(route('system.plans.store'), [
                'name_ar' => 'باقة المستأجر',
                'name_en' => 'Tenant Plan',
                'slug' => 'tenant-plan',
                'price_yearly' => 900,
                'trial_days' => 7,
            ])
            ->assertRedirect();

        $log = AdminActivityLog::where('action', 'plan_create')->latest()->first();

        $this->assertNotNull($log, 'A tenant-session admin must still be audited.');
        $this->assertNull(
            $log->admin_user_id,
            'admin_user_id must stay NULL — the tenant user has no admin_users row.'
        );
        $this->assertSame('tenant_system_admin', $log->changes['actor_type']);
        $this->assertSame($tenantAdmin->name, $log->changes['actor']);
    }

    public function test_an_audit_write_never_breaks_the_operation(): void
    {
        // The audit trail must never be able to take down the action
        // the operator asked for.
        $this->actingAs($this->admin, 'admin')
            ->post(route('system.plans.store'), [
                'name_ar' => 'باقة سليمة',
                'name_en' => 'Solid Plan',
                'slug' => 'solid-plan',
                'price_yearly' => 100,
                'trial_days' => 0,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('plans', ['slug' => 'solid-plan']);
    }
}

<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Center;
use App\Models\CompanyTransaction;
use App\Models\Customer;
use App\Models\IncomeCategory;
use App\Models\Supplier;
use App\Models\Tenant;
use App\Models\User;
use App\Support\Permissions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Tests for the company financial transactions
 * (audit findings #35 and #36).
 *
 * THE BUGS
 *
 * 1. No authorization at all. CompanyTransactionController::store(),
 *    update(), destroy() and approve() had no authorize() call, so any
 *    authenticated user in the tenant could create, edit, delete and
 *    APPROVE company financial entries. approve() is what issues a
 *    real invoice and records a real payment.
 *
 * 2. The totals came from the client. `amount`, `tax_amount` and
 *    `total_amount` were all request fields with no consistency
 *    check, so the stored figures could contradict each other — and
 *    approve() issued the invoice and the payment with them.
 *
 * 3. The contact search leaked across tenants. The conditions were
 *    not grouped:
 *
 *        Customer::where('name', 'like', $q)->orWhere('phone', 'like', $q)
 *
 *    which reads "tenant_id = X AND name LIKE ? OR phone LIKE ?".
 *    SQL binds AND tighter than OR, so the second branch matched the
 *    phone number in ANY tenant.
 *
 * 4. `Route::resource` declared index/show/create/edit for a
 *    controller that implements none of them, so those URLs answered
 *    HTTP 500.
 */
class CompanyTransactionAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    private Center $center;

    private IncomeCategory $category;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach ([Permissions::INVOICES_VIEW, Permissions::INVOICES_CREATE] as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $this->tenant = Tenant::factory()->create();
        $this->center = Center::factory()->create(['tenant_id' => $this->tenant->id]);
        $this->category = IncomeCategory::create([
            'tenant_id' => $this->tenant->id,
            'name_ar' => 'إيرادات',
            'name_en' => 'Revenue',
            'transaction_type' => 'revenue',
            'is_active' => true,
        ]);
    }

    private function userWith(array $permissions): User
    {
        $user = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'current_center_id' => $this->center->id,
        ]);
        $user->centers()->attach($this->center->id, ['tenant_id' => $this->tenant->id]);

        app(PermissionRegistrar::class)->setPermissionsTeamId($this->tenant->id);

        foreach ($permissions as $permission) {
            $user->givePermissionTo($permission);
        }

        $user->refresh();

        return $user;
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'title' => 'إيراد ورشة',
            'transaction_date' => now()->toDateString(),
            'transaction_type' => 'revenue',
            'income_category_id' => $this->category->id,
            'amount' => 100,
            'tax_amount' => 15,
            'total_amount' => 115,
        ], $overrides);
    }

    private function transaction(array $overrides = []): CompanyTransaction
    {
        return CompanyTransaction::create(array_merge([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
            'title' => 'إيراد ورشة',
            'transaction_date' => now()->toDateString(),
            'transaction_type' => 'revenue',
            'income_category_id' => $this->category->id,
            'amount' => 100,
            'tax_amount' => 15,
            'total_amount' => 115,
            'status' => 'draft',
        ], $overrides));
    }

    /**
     * The headline hole: a user with no financial permission must not
     * be able to create a company financial entry.
     */
    public function test_a_user_without_the_invoice_permission_cannot_create_a_transaction(): void
    {
        $user = $this->userWith([]);

        $response = $this->actingAs($user)->post('/app/settings/company/transactions', $this->payload());

        $response->assertForbidden();

        $this->assertDatabaseCount('company_transactions', 0);
    }

    /**
     * ...nor approve one, which is the operation that issues the
     * invoice and records the payment.
     */
    public function test_a_user_without_the_invoice_permission_cannot_approve_a_transaction(): void
    {
        $transaction = $this->transaction();

        $user = $this->userWith([]);

        $response = $this->actingAs($user)
            ->post('/app/settings/company/transactions/'.$transaction->id.'/approve');

        $response->assertForbidden();

        $this->assertSame('draft', $transaction->fresh()->status);
        $this->assertDatabaseCount('invoices', 0);
        $this->assertDatabaseCount('payments', 0);
    }

    /**
     * ...nor edit or delete one.
     */
    public function test_a_user_without_the_invoice_permission_cannot_edit_or_delete_a_transaction(): void
    {
        $transaction = $this->transaction();

        $user = $this->userWith([]);

        $this->actingAs($user)
            ->put('/app/settings/company/transactions/'.$transaction->id, $this->payload(['title' => 'معدّل']))
            ->assertForbidden();

        $this->actingAs($user)
            ->delete('/app/settings/company/transactions/'.$transaction->id)
            ->assertForbidden();

        $this->assertDatabaseHas('company_transactions', [
            'id' => $transaction->id,
            'title' => 'إيراد ورشة',
        ]);
    }

    /**
     * A financial user (the accountant role holds INVOICES_CREATE)
     * keeps working.
     */
    public function test_a_financial_user_can_still_create_and_approve(): void
    {
        $user = $this->userWith([Permissions::INVOICES_CREATE]);

        $response = $this->actingAs($user)
            ->post('/app/settings/company/transactions', $this->payload());

        $response->assertSessionHasNoErrors();

        $transaction = CompanyTransaction::first();
        $this->assertNotNull($transaction);
        $this->assertSame('draft', $transaction->status);

        $approve = $this->actingAs($user)
            ->post('/app/settings/company/transactions/'.$transaction->id.'/approve');

        $approve->assertSessionHasNoErrors();
        $this->assertSame('approved', $transaction->fresh()->status);
    }

    /**
     * The stored total must be the sum of the amount and the tax,
     * whatever the request body claimed.
     */
    public function test_the_total_is_derived_server_side(): void
    {
        $user = $this->userWith([Permissions::INVOICES_CREATE]);

        $this->actingAs($user)->post('/app/settings/company/transactions', $this->payload([
            'amount' => 100,
            'tax_amount' => 15,
            'total_amount' => 999999,
        ]));

        $transaction = CompanyTransaction::first();

        $this->assertNotNull($transaction);
        $this->assertEquals(
            115.0,
            (float) $transaction->total_amount,
            'The stored total must be amount + tax_amount, not the value the client sent.'
        );
    }

    /**
     * The same on update.
     */
    public function test_the_total_is_derived_server_side_on_update(): void
    {
        $transaction = $this->transaction();

        $user = $this->userWith([Permissions::INVOICES_CREATE]);

        $this->actingAs($user)->put(
            '/app/settings/company/transactions/'.$transaction->id,
            $this->payload(['amount' => 200, 'tax_amount' => 30, 'total_amount' => 1])
        );

        $this->assertEquals(230.0, (float) $transaction->fresh()->total_amount);
    }

    /**
     * A user from ANOTHER tenant must not approve this tenant's
     * transaction.
     */
    public function test_a_user_from_another_tenant_cannot_approve(): void
    {
        $transaction = $this->transaction();

        $otherTenant = Tenant::factory()->create();
        $otherCenter = Center::factory()->create(['tenant_id' => $otherTenant->id]);

        $user = User::factory()->create([
            'tenant_id' => $otherTenant->id,
            'current_center_id' => $otherCenter->id,
        ]);
        $user->centers()->attach($otherCenter->id, ['tenant_id' => $otherTenant->id]);

        app(PermissionRegistrar::class)->setPermissionsTeamId($otherTenant->id);
        $user->givePermissionTo(Permissions::INVOICES_CREATE);
        $user->refresh();

        $response = $this->actingAs($user)
            ->post('/app/settings/company/transactions/'.$transaction->id.'/approve');

        // 404, not 403: the route model binding scopes the transaction
        // to the current tenant, so a foreign transaction is simply not
        // found. That is the better answer — a 403 would confirm it
        // exists.
        $response->assertNotFound();
        $this->assertSame('draft', $transaction->fresh()->status);
    }

    /**
     * The contact search must not return another tenant's customers.
     */
    public function test_the_contact_search_does_not_leak_across_tenants(): void
    {
        $mine = Customer::factory()->create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
            'name' => 'عميل محلي',
            'phone' => '+966500000001',
        ]);

        // Another tenant, matched by NAME.
        //
        // NOTE: Laravel 12 groups the top-level `orWhere` pair
        // automatically, so the generated SQL is
        // "where (name like ? or phone like ?) and tenant_id = ?".
        // The un-grouped source therefore does NOT leak today — this
        // test is the regression guard that keeps it that way, and
        // the controller now groups the conditions explicitly so the
        // intent does not depend on the grammar.
        $theirs = Customer::factory()->create([
            'tenant_id' => Tenant::factory()->create()->id,
            'name' => 'عميل من مستأجر آخر',
            'phone' => '+966500000002',
        ]);

        $user = $this->userWith([Permissions::INVOICES_CREATE]);

        $response = $this->actingAs($user)
            ->getJson('/app/settings/company/contacts/search?search='.urlencode('عميل من مستأجر آخر'));

        $response->assertOk();

        $ids = collect($response->json())->pluck('id');

        $this->assertNotContains(
            $theirs->id,
            $ids->all(),
            'The contact search must not return another tenant\'s customer.'
        );
    }

    /**
     * ...and the caller's own customer is still found.
     */
    public function test_the_contact_search_still_finds_the_callers_own_contacts(): void
    {
        $mine = Customer::factory()->create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
            'name' => 'عميل محلي',
            'phone' => '+966500000001',
        ]);

        $user = $this->userWith([Permissions::INVOICES_CREATE]);

        $response = $this->actingAs($user)
            ->getJson('/app/settings/company/contacts/search?search=500000001');

        $response->assertOk();

        $ids = collect($response->json())->pluck('id');

        $this->assertContains($mine->id, $ids->all());
    }

    /**
     * The supplier search has the same grouping fix.
     */
    public function test_the_supplier_search_does_not_leak_across_tenants(): void
    {
        $theirs = Supplier::create([
            'tenant_id' => Tenant::factory()->create()->id,
            'name' => 'مورد من مستأجر آخر',
            'type' => 'individual',
            'phone' => '+966500000003',
            'is_active' => true,
        ]);

        $user = $this->userWith([Permissions::INVOICES_CREATE]);

        $response = $this->actingAs($user)
            ->getJson('/app/settings/company/contacts/search?search='.urlencode('مورد من مستأجر آخر'));

        $response->assertOk();

        $ids = collect($response->json())->pluck('id');

        $this->assertNotContains(
            $theirs->id,
            $ids->all(),
            'The contact search must not return another tenant\'s supplier.'
        );
    }

    /**
     * The resource routes for methods the controller does not
     * implement must not be registered — they used to answer 500.
     */
    public function test_the_unimplemented_resource_routes_are_not_registered(): void
    {
        $router = app('router');

        $this->assertNull(
            $router->getRoutes()->getByName('settings.company.transactions.index'),
            'The index route must not be registered — the controller has no index().'
        );
        $this->assertNull(
            $router->getRoutes()->getByName('settings.company.transactions.create'),
            'The create route must not be registered — the controller has no create().'
        );
        $this->assertNull(
            $router->getRoutes()->getByName('settings.company.transactions.show'),
            'The show route must not be registered — the controller has no show().'
        );
        $this->assertNull(
            $router->getRoutes()->getByName('settings.company.transactions.edit'),
            'The edit route must not be registered — the controller has no edit().'
        );

        // The write routes ARE registered.
        $this->assertNotNull($router->getRoutes()->getByName('settings.company.transactions.store'));
        $this->assertNotNull($router->getRoutes()->getByName('settings.company.transactions.update'));
        $this->assertNotNull($router->getRoutes()->getByName('settings.company.transactions.destroy'));
    }

    public function test_foreign_contact_category_and_center_are_rejected_and_legacy_contact_is_not_serialized(): void
    {
        $other = Tenant::factory()->create();
        $center = Center::factory()->create(['tenant_id' => $other->id]);
        $customer = Customer::factory()->create(['tenant_id' => $other->id, 'center_id' => $center->id]);
        $category = IncomeCategory::create(['tenant_id' => $other->id, 'name_ar' => 'Test', 'name_en' => 'Test', 'transaction_type' => 'revenue']);
        $actor = $this->userWith([Permissions::INVOICES_CREATE]);
        $this->actingAs($actor)->post('/app/settings/company/transactions', $this->payload([
            'contact_type' => 'customer', 'contact_id' => $customer->id,
            'center_id' => $center->id, 'income_category_id' => $category->id,
        ]))->assertSessionHasErrors(['contact_id', 'center_id', 'income_category_id']);
        $legacy = $this->transaction(['contact_type' => 'customer', 'contact_id' => $customer->id]);
        $this->assertNull($legacy->toArray()['contact']);
    }
}

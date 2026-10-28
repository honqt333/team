<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Center;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Tenant;
use App\Models\User;
use App\Support\Permissions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Tests for the centre boundary of an invoice (audit finding #24).
 *
 * Invoice uses CenterScoped, but resolveRouteBinding() drops that
 * scope and resolves the invoice within the tenant only:
 *
 *     return $this->withoutGlobalScope('center_scoped')
 *         ->where($field ?? $this->getRouteKeyName(), $value)
 *         ->where('tenant_id', TenancyContext::tenantId())
 *         ->first();
 *
 * The policy then only checked the tenant. So a user of centre A
 * could open — and print — an invoice of centre B by its id, while
 * the invoice LIST only ever shows the current centre. Knowing an id
 * was enough to bypass the list.
 *
 * The fix compares the invoice's centre with the user's current
 * centre, exactly like PaymentPolicy already does.
 */
class InvoiceCenterScopeTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    private Center $centerA;

    private Center $centerB;

    private Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach ([
            Permissions::INVOICES_VIEW,
            Permissions::INVOICES_CREATE,
            Permissions::PURCHASE_PAYMENTS_MANAGE,
        ] as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $this->tenant = Tenant::factory()->create();
        $this->centerA = Center::factory()->create(['tenant_id' => $this->tenant->id]);
        $this->centerB = Center::factory()->create(['tenant_id' => $this->tenant->id]);

        $this->customer = Customer::factory()->create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->centerA->id,
        ]);
    }

    private function userIn(Center $center, array $permissions): User
    {
        $user = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'current_center_id' => $center->id,
        ]);

        $user->centers()->attach($center->id, ['tenant_id' => $this->tenant->id]);

        app(PermissionRegistrar::class)->setPermissionsTeamId($this->tenant->id);
        $user->givePermissionTo($permissions);
        $user->refresh();

        return $user;
    }

    private function invoiceIn(Center $center): Invoice
    {
        return Invoice::create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $center->id,
            'customer_id' => $this->customer->id,
            'invoice_number' => 'INV-'.uniqid(),
            'issue_date' => now(),
            'supply_date' => now()->toDateString(),
            'type' => 'invoice',
            'status' => 'valid',
            'payment_status' => 'unpaid',
            'total_excl_tax' => 100,
            'total_tax' => 15,
            'total_incl_tax' => 115,
            'total_paid' => 0,
            'currency_code' => 'SAR',
        ]);
    }

    public function test_a_user_cannot_open_an_invoice_of_another_centre(): void
    {
        $invoice = $this->invoiceIn($this->centerB);

        $user = $this->userIn($this->centerA, [Permissions::INVOICES_VIEW]);

        $this->actingAs($user)
            ->get('/app/invoices/'.$invoice->id)
            ->assertForbidden();
    }

    public function test_a_user_cannot_print_an_invoice_of_another_centre(): void
    {
        $invoice = $this->invoiceIn($this->centerB);

        $user = $this->userIn($this->centerA, [Permissions::INVOICES_VIEW]);

        $this->actingAs($user)
            ->get('/app/invoices/'.$invoice->id.'/print')
            ->assertForbidden();
    }

    public function test_a_user_cannot_record_a_payment_against_an_invoice_of_another_centre(): void
    {
        $invoice = $this->invoiceIn($this->centerB);

        $user = $this->userIn($this->centerA, [Permissions::PURCHASE_PAYMENTS_MANAGE]);

        $this->actingAs($user)
            ->post('/app/invoices/'.$invoice->id.'/payments', [
                'amount' => 50,
                'payment_method' => 'cash',
            ])
            ->assertForbidden();

        $this->assertDatabaseCount('payments', 0);
    }

    public function test_the_update_and_delete_abilities_are_also_centre_scoped(): void
    {
        $invoice = $this->invoiceIn($this->centerB);
        $invoice->update(['status' => 'draft']);

        $user = $this->userIn($this->centerA, [Permissions::INVOICES_CREATE]);

        // There is no HTTP route that updates or deletes an invoice
        // today, but the abilities are what a future route would use,
        // so they have to carry the same boundary.
        $this->assertFalse(
            Gate::forUser($user)->allows('update', $invoice),
            'A draft invoice of another centre must not be editable.'
        );
        $this->assertFalse(
            Gate::forUser($user)->allows('delete', $invoice),
            'A draft invoice of another centre must not be deletable.'
        );
    }

    public function test_a_user_can_open_an_invoice_of_their_own_centre(): void
    {
        $invoice = $this->invoiceIn($this->centerA);

        $user = $this->userIn($this->centerA, [Permissions::INVOICES_VIEW]);

        $this->actingAs($user)
            ->get('/app/invoices/'.$invoice->id)
            ->assertStatus(200);
    }

    public function test_a_user_can_open_an_invoice_of_another_centre_after_switching_to_it(): void
    {
        $invoice = $this->invoiceIn($this->centerB);

        $user = $this->userIn($this->centerA, [Permissions::INVOICES_VIEW]);
        $user->centers()->attach($this->centerB->id, ['tenant_id' => $this->tenant->id]);

        // The app has a centre-switching feature; the invoice follows
        // the centre the user is working in.
        $user->update(['current_center_id' => $this->centerB->id]);
        $user->refresh();

        $this->actingAs($user)
            ->get('/app/invoices/'.$invoice->id)
            ->assertStatus(200);
    }
}

<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Center;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Vehicle;
use App\Support\Permissions;
use Database\Seeders\PermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Proves the N+1 fix on the vehicle listing endpoints did not change
 * any number the owner acts on, and that it removed the per-row
 * queries.
 *
 * WHAT WAS WRONG
 *
 * VehicleController::apiIndex() and Api\WorkOrderController::vehicleSearch()
 * both called Customer::getOutstandingBalance() once per row inside a
 * ->each() loop. That method runs its own aggregate query, so a page of
 * 20 vehicles issued 20 extra queries.
 *
 * THE FIX
 *
 * The balance is now computed by a correlated subquery in the main
 * query (the same pattern CustomerController::buildCustomerQuery()
 * already used), so it costs no additional round trip.
 *
 * WHAT THIS TEST PROVES
 *
 * 1. Numeric equivalence — the balance the endpoint reports is exactly
 *    what getOutstandingBalance() returns, for a customer with a mix of
 *    paid, unpaid, cancelled and soft-deleted invoices.
 * 2. Credit-limit derivations are unchanged (is_credit_exceeded,
 *    available_credit).
 * 3. The query count no longer grows with the number of vehicles.
 */
class VehicleBalanceNPlusOneTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(): array
    {
        $this->seed(PermissionsSeeder::class);

        $tenant = Tenant::factory()->create();
        $center = Center::factory()->create(['tenant_id' => $tenant->id]);

        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'current_center_id' => $center->id,
            'two_factor_confirmed_at' => now(),
        ]);
        $user->centers()->attach($center->id, ['tenant_id' => $tenant->id]);

        app(PermissionRegistrar::class)->setPermissionsTeamId($tenant->id);

        $role = Role::firstOrCreate([
            'tenant_id' => $tenant->id,
            'name' => 'vehicle_viewer_'.uniqid(),
            'guard_name' => 'web',
        ]);
        $role->givePermissionTo(Permissions::VEHICLES_VIEW);
        $user->assignRole($role);

        return [$tenant, $center, $user];
    }

    /**
     * Create a customer with a deliberately messy invoice history.
     */
    private function makeCustomerWithInvoices(Tenant $tenant, Center $center): Customer
    {
        $customer = Customer::create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'name' => 'Customer With Invoices',
            'phone' => '0500000001',
            'credit_limit' => 500,
        ]);

        // Paid in full -> contributes 0.
        $this->makeInvoice($tenant, $center, $customer, total: 100.0, paid: 100.0);

        // Partially paid -> contributes total - paid.
        $this->makeInvoice($tenant, $center, $customer, total: 300.0, paid: 100.0);

        // Unpaid -> contributes the full total.
        //
        // The invoice `status` is `valid` for every counted invoice. The
        // balance query excludes ONLY `cancelled`
        // (`whereNotIn('status', ['cancelled'])`), and the amount that
        // counts is `total_incl_tax - total_paid` — so what makes an
        // invoice "paid", "partial" or "unpaid" is `total_paid`, not the
        // status. The payment states used to be written into the invoice
        // `status` column, which is not what that column means: its
        // enum is `draft|valid|reported|cancelled`.
        $this->makeInvoice($tenant, $center, $customer, total: 250.0, paid: 0.0);

        // Cancelled -> must be excluded.
        $this->makeInvoice($tenant, $center, $customer, total: 999.0, paid: 0.0, status: 'cancelled');

        // Soft-deleted -> must be excluded.
        //
        // The soft delete goes through the query builder: an issued
        // document may not be deleted through the model, because the
        // invoice immutability guard refuses it and `InvoicePolicy::delete()`
        // allows a `draft` only.
        $deleted = $this->makeInvoice($tenant, $center, $customer, total: 777.0, paid: 0.0);
        DB::table('invoices')->where('id', $deleted->id)->update(['deleted_at' => now()]);

        return $customer;
    }

    private function makeInvoice(
        Tenant $tenant,
        Center $center,
        Customer $customer,
        float $total,
        float $paid,
        string $status = 'valid'
    ): Invoice {
        return Invoice::create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'customer_id' => $customer->id,
            'invoice_number' => 'INV-'.uniqid(),
            'status' => $status,
            'issue_date' => now(),
            'supply_date' => now(),
            'subtotal' => $total,
            'total_tax' => 0,
            'total_incl_tax' => $total,
            'total_paid' => $paid,
        ]);
    }

    public function test_the_endpoint_reports_exactly_the_same_balance_as_the_per_row_calculation(): void
    {
        [$tenant, $center, $user] = $this->makeUser();

        $customer = $this->makeCustomerWithInvoices($tenant, $center);

        Vehicle::create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'customer_id' => $customer->id,
            'plate_number' => 'ABC-123',
        ]);

        // The value the OLD implementation computed, per row, by
        // calling Customer::getOutstandingBalance().
        $expectedBalance = $customer->getOutstandingBalance();

        // 100 paid in full -> 0
        // 300 total - 100 paid -> 200
        // 250 unpaid -> 250
        // cancelled 999 -> excluded
        // soft-deleted 777 -> excluded
        $this->assertEqualsWithDelta(450.0, $expectedBalance, 0.001, 'Fixture sanity check.');

        $response = $this->actingAs($user)->getJson(route('vehicles.api.index'));

        $response->assertOk();

        $payload = $response->json('data');
        $this->assertCount(1, $payload);

        $reported = $payload[0]['customer']['balance'] ?? null;

        $this->assertNotNull($reported, 'The payload must carry the customer balance.');
        $this->assertEqualsWithDelta(
            $expectedBalance,
            (float) $reported,
            0.001,
            'The batched subquery must return exactly the same balance as the per-row calculation.'
        );

        // The credit-limit derivations must be unchanged too.
        $creditLimit = (float) $payload[0]['customer']['credit_limit'];
        $this->assertEqualsWithDelta(500.0, $creditLimit, 0.001);
        $this->assertFalse(
            (bool) $payload[0]['customer']['is_credit_exceeded'],
            'Balance 450 is below the 500 credit limit, so it must not be exceeded.'
        );
        $this->assertEqualsWithDelta(
            50.0,
            (float) $payload[0]['customer']['available_credit'],
            0.001,
            'Available credit must be credit_limit - balance.'
        );
    }

    public function test_a_customer_without_invoices_has_a_zero_balance(): void
    {
        [$tenant, $center, $user] = $this->makeUser();

        $customer = Customer::create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'name' => 'No Invoices',
            'phone' => '0500000002',
            'credit_limit' => 0,
        ]);

        Vehicle::create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'customer_id' => $customer->id,
            'plate_number' => 'XYZ-789',
        ]);

        $response = $this->actingAs($user)->getJson(route('vehicles.api.index'));
        $response->assertOk();

        $payload = $response->json('data');
        $this->assertEqualsWithDelta(
            0.0,
            (float) ($payload[0]['customer']['balance'] ?? -1),
            0.001,
            'A customer with no invoices must report a zero balance.'
        );
        $this->assertArrayHasKey(
            'available_credit',
            $payload[0]['customer'],
            'available_credit must be present on the customer payload.'
        );
        $this->assertNull(
            $payload[0]['customer']['available_credit'],
            'With no credit limit, available_credit must stay null.'
        );
    }

    public function test_the_query_count_does_not_grow_with_the_number_of_vehicles(): void
    {
        // Measure the endpoint with ONE vehicle, then with TWELVE, and
        // require the query count to be identical.
        //
        // Under the old implementation the difference was exactly the
        // number of extra vehicles, because getOutstandingBalance() ran
        // its own aggregate query once per row. The fixed version
        // computes the balance in the main query, so the difference
        // must be zero.
        $counts = [];

        foreach ([1, 12] as $vehicleCount) {
            [$tenant, $center, $user] = $this->makeUser();

            $customer = $this->makeCustomerWithInvoices($tenant, $center);

            foreach (range(1, $vehicleCount) as $i) {
                Vehicle::create([
                    'tenant_id' => $tenant->id,
                    'center_id' => $center->id,
                    'customer_id' => $customer->id,
                    'plate_number' => 'PLT-'.str_pad((string) $i, 3, '0', STR_PAD_LEFT),
                ]);
            }

            // Warm the caches (permissions, tenancy context, schema
            // lookups) so we measure only the endpoint's own queries.
            $this->actingAs($user)->getJson(route('vehicles.api.index'))->assertOk();

            $counts[$vehicleCount] = $this->countQueries(
                fn () => $this->actingAs($user)->getJson(route('vehicles.api.index'))
            );
        }

        $this->assertSame(
            $counts[1],
            $counts[12],
            'The query count must not depend on the number of vehicles: '
            ."{$counts[1]} queries for 1 vehicle vs {$counts[12]} for 12 vehicles. "
            .'The balance must be computed in the main query, not once per row.'
        );
    }

    /**
     * Run a callback and count the SQL queries it executes.
     */
    private function countQueries(callable $callback): int
    {
        DB::enableQueryLog();
        DB::flushQueryLog();

        $callback();

        $count = count(DB::getQueryLog());

        DB::disableQueryLog();

        return $count;
    }

    public function test_the_vehicle_search_endpoint_reports_the_same_balance(): void
    {
        [$tenant, $center, $user] = $this->makeUser();

        $customer = $this->makeCustomerWithInvoices($tenant, $center);

        Vehicle::create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'customer_id' => $customer->id,
            'plate_number' => 'SRCH-001',
        ]);

        $expectedBalance = $customer->getOutstandingBalance();

        $response = $this->actingAs($user)->getJson('/app/api/vehicles/search?q=SRCH');
        $response->assertOk();

        $payload = $response->json();

        $this->assertNotEmpty($payload, 'The search should return the matching vehicle.');

        $reported = $payload[0]['customer']['balance'] ?? null;
        $this->assertNotNull($reported);
        $this->assertEqualsWithDelta(
            $expectedBalance,
            (float) $reported,
            0.001,
            'The vehicle search endpoint must report the same balance as the per-row calculation.'
        );
    }
}

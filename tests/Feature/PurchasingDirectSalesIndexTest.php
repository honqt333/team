<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\InvoiceType;
use App\Models\Center;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Tenant;
use App\Models\User;
use App\Models\WorkOrder;
use App\Support\Permissions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class PurchasingDirectSalesIndexTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    private Center $center;

    private User $user;

    private Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::factory()->create();
        $this->center = Center::factory()->create(['tenant_id' => $this->tenant->id]);

        $this->app->make(PermissionRegistrar::class)->forgetCachedPermissions();
        Permission::findOrCreate(Permissions::INVOICES_VIEW, 'web');
        Permission::findOrCreate('inventory.view', 'web');

        $this->user = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'current_center_id' => $this->center->id,
        ]);
        $this->user->centers()->attach($this->center->id, ['tenant_id' => $this->tenant->id]);

        $this->app->make(PermissionRegistrar::class)->setPermissionsTeamId($this->tenant->id);
        $this->user->givePermissionTo(Permissions::INVOICES_VIEW);
        $this->user->givePermissionTo('inventory.view');

        $this->customer = Customer::factory()->create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
        ]);
    }

    public function test_purchasing_sales_index_shows_only_direct_sales_without_work_orders(): void
    {
        // 1. Direct sales invoice (work_order_id = null)
        $directSale = Invoice::factory()->create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
            'customer_id' => $this->customer->id,
            'work_order_id' => null,
            'type' => InvoiceType::INVOICE->value,
            'invoice_number' => 'INV-DIRECT-0001',
            'status' => 'valid',
            'payment_status' => 'paid',
            'total_incl_tax' => 300,
        ]);

        // 2. Direct credit note (return of direct sale, work_order_id = null)
        $directReturn = Invoice::factory()->create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
            'customer_id' => $this->customer->id,
            'work_order_id' => null,
            'original_invoice_id' => $directSale->id,
            'type' => InvoiceType::CREDIT_NOTE->value,
            'invoice_number' => 'CN-DIRECT-0001',
            'status' => 'valid',
            'payment_status' => 'paid',
            'total_incl_tax' => 100,
        ]);

        // 3. Work order invoice (MUST NOT appear in purchasing sales)
        $workOrder = WorkOrder::factory()->create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
            'customer_id' => $this->customer->id,
        ]);

        $woInvoice = Invoice::factory()->create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
            'customer_id' => $this->customer->id,
            'work_order_id' => $workOrder->id,
            'type' => InvoiceType::INVOICE->value,
            'invoice_number' => 'INV-WO-0001',
            'status' => 'valid',
            'payment_status' => 'paid',
            'total_incl_tax' => 1500,
        ]);

        // Request purchasing sales index
        $response = $this->actingAs($this->user)->get(route('app.purchasing.sales.index'));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Purchasing/Sales/Index')
            ->has('invoices.data', 1)
            ->where('invoices.data.0.id', $directSale->id)
            ->where('invoices.data.0.invoice_number', 'INV-DIRECT-0001')
            ->has('returns.data', 1)
            ->where('returns.data.0.id', $directReturn->id)
            ->where('returns.data.0.invoice_number', 'CN-DIRECT-0001')
        );

        // Purchasing Hub should reflect direct sales count
        $hubResponse = $this->actingAs($this->user)->get(route('app.purchasing.hub'));
        $hubResponse->assertOk();
        $hubResponse->assertInertia(fn (Assert $page) => $page
            ->component('Purchasing/Hub')
            ->where('salesCount', 1)
        );
    }
}

<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Center;
use App\Models\PurchaseInvoice;
use App\Models\PurchaseReturnInvoice;
use App\Models\Supplier;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Warehouse;
use Database\Seeders\PermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class PurchaseInvoicesCrudTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;

    protected Center $center;

    protected User $user;

    protected Warehouse $warehouse;

    protected Supplier $supplier;

    protected function setUp(): void
    {
        parent::setUp();

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
        $this->seed(PermissionsSeeder::class);

        $this->tenant = Tenant::factory()->create();
        $this->center = Center::factory()->create(['tenant_id' => $this->tenant->id]);

        $this->user = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'current_center_id' => $this->center->id,
        ]);

        $this->user->centers()->attach($this->center->id, ['tenant_id' => $this->tenant->id]);

        app(PermissionRegistrar::class)->setPermissionsTeamId($this->tenant->id);
        $this->user->givePermissionTo('purchasing.invoices.view');
        $this->user->givePermissionTo('purchasing.invoices.create');
        $this->user->refresh();

        $this->warehouse = Warehouse::create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
            'name' => 'Main Warehouse',
            'is_active' => true,
        ]);

        $this->supplier = Supplier::create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
            'name' => 'Al-Nasr Parts',
            'is_active' => true,
        ]);
    }

    public function test_authorized_user_can_view_purchase_invoices_index(): void
    {
        $invoice = PurchaseInvoice::create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
            'supplier_id' => $this->supplier->id,
            'warehouse_id' => $this->warehouse->id,
            'code' => 'PI-2026-0001',
            'invoice_number' => 'SUP-INV-100',
            'issue_date' => '2026-11-01',
            'subtotal' => 1000,
            'tax_amount' => 150,
            'total' => 1150,
            'balance' => 1150,
            'status' => PurchaseInvoice::STATUS_OPEN,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('app.invoices.purchases.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Invoices/Purchasing/Index')
            ->has('invoices.data', 1)
            ->where('invoices.data.0.code', 'PI-2026-0001')
            ->where('invoices.data.0.total', fn ($total) => (float) $total === 1150.0)
            ->has('returns')
            ->has('filters')
        );
    }

    public function test_purchasing_purchases_index_redirects_to_invoices_purchases_index(): void
    {
        $response = $this->actingAs($this->user)
            ->get(route('app.purchasing.purchases.index', ['status' => 'open']));

        $response->assertRedirect(route('app.invoices.purchases.index', ['status' => 'open']));
    }

    public function test_search_filters_invoices_by_code_and_supplier(): void
    {
        $otherSupplier = Supplier::create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
            'name' => 'Gulf Auto Tech',
            'is_active' => true,
        ]);

        PurchaseInvoice::create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
            'supplier_id' => $this->supplier->id,
            'warehouse_id' => $this->warehouse->id,
            'code' => 'PI-2026-0001',
            'invoice_number' => 'INV-MATCH',
            'issue_date' => '2026-11-01',
            'subtotal' => 500,
            'tax_amount' => 75,
            'total' => 575,
            'balance' => 575,
            'status' => PurchaseInvoice::STATUS_OPEN,
        ]);

        PurchaseInvoice::create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
            'supplier_id' => $otherSupplier->id,
            'warehouse_id' => $this->warehouse->id,
            'code' => 'PI-2026-0002',
            'invoice_number' => 'INV-OTHER',
            'issue_date' => '2026-11-02',
            'subtotal' => 800,
            'tax_amount' => 120,
            'total' => 920,
            'balance' => 920,
            'status' => PurchaseInvoice::STATUS_DRAFT,
        ]);

        // Search by code
        $response = $this->actingAs($this->user)
            ->get(route('app.invoices.purchases.index', ['search' => 'PI-2026-0001']));

        $response->assertInertia(fn (Assert $page) => $page
            ->has('invoices.data', 1)
            ->where('invoices.data.0.code', 'PI-2026-0001')
        );

        // Search by supplier name
        $responseSupplier = $this->actingAs($this->user)
            ->get(route('app.invoices.purchases.index', ['search' => 'Gulf Auto']));

        $responseSupplier->assertInertia(fn (Assert $page) => $page
            ->has('invoices.data', 1)
            ->where('invoices.data.0.code', 'PI-2026-0002')
        );
    }

    public function test_status_filter(): void
    {
        PurchaseInvoice::create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
            'supplier_id' => $this->supplier->id,
            'warehouse_id' => $this->warehouse->id,
            'code' => 'PI-OPEN',
            'issue_date' => '2026-11-01',
            'subtotal' => 100,
            'tax_amount' => 15,
            'total' => 115,
            'balance' => 115,
            'status' => PurchaseInvoice::STATUS_OPEN,
        ]);

        PurchaseInvoice::create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
            'supplier_id' => $this->supplier->id,
            'warehouse_id' => $this->warehouse->id,
            'code' => 'PI-PAID',
            'issue_date' => '2026-11-01',
            'subtotal' => 200,
            'tax_amount' => 30,
            'total' => 230,
            'balance' => 0,
            'status' => PurchaseInvoice::STATUS_PAID,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('app.invoices.purchases.index', ['status' => PurchaseInvoice::STATUS_PAID]));

        $response->assertInertia(fn (Assert $page) => $page
            ->has('invoices.data', 1)
            ->where('invoices.data.0.code', 'PI-PAID')
        );
    }

    public function test_whitelist_sorting(): void
    {
        PurchaseInvoice::create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
            'supplier_id' => $this->supplier->id,
            'warehouse_id' => $this->warehouse->id,
            'code' => 'PI-A',
            'issue_date' => '2026-11-01',
            'subtotal' => 100,
            'tax_amount' => 15,
            'total' => 115,
            'balance' => 115,
            'status' => PurchaseInvoice::STATUS_OPEN,
        ]);

        PurchaseInvoice::create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
            'supplier_id' => $this->supplier->id,
            'warehouse_id' => $this->warehouse->id,
            'code' => 'PI-B',
            'issue_date' => '2026-11-05',
            'subtotal' => 500,
            'tax_amount' => 75,
            'total' => 575,
            'balance' => 575,
            'status' => PurchaseInvoice::STATUS_OPEN,
        ]);

        // Sort by total ascending
        $ascResponse = $this->actingAs($this->user)
            ->get(route('app.invoices.purchases.index', ['sort' => 'total', 'direction' => 'asc']));

        $ascResponse->assertInertia(fn (Assert $page) => $page
            ->has('invoices.data', 2)
            ->where('invoices.data.0.code', 'PI-A')
            ->where('invoices.data.1.code', 'PI-B')
        );

        // Sort by total descending
        $descResponse = $this->actingAs($this->user)
            ->get(route('app.invoices.purchases.index', ['sort' => 'total', 'direction' => 'desc']));

        $descResponse->assertInertia(fn (Assert $page) => $page
            ->has('invoices.data', 2)
            ->where('invoices.data.0.code', 'PI-B')
            ->where('invoices.data.1.code', 'PI-A')
        );
    }

    public function test_tenant_and_center_scoping_isolation(): void
    {
        $otherTenant = Tenant::factory()->create();
        $otherCenter = Center::factory()->create(['tenant_id' => $otherTenant->id]);
        $otherSupplier = Supplier::create([
            'tenant_id' => $otherTenant->id,
            'center_id' => $otherCenter->id,
            'name' => 'Other Tenant Supplier',
            'is_active' => true,
        ]);

        PurchaseInvoice::create([
            'tenant_id' => $otherTenant->id,
            'center_id' => $otherCenter->id,
            'supplier_id' => $otherSupplier->id,
            'code' => 'PI-OTHER-TENANT',
            'issue_date' => '2026-11-01',
            'subtotal' => 100,
            'tax_amount' => 15,
            'total' => 115,
            'balance' => 115,
            'status' => PurchaseInvoice::STATUS_OPEN,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('app.invoices.purchases.index'));

        $response->assertInertia(fn (Assert $page) => $page
            ->has('invoices.data', 0)
        );
    }

    public function test_returns_tab_displays_and_sorts_purchase_returns(): void
    {
        $invoice = PurchaseInvoice::create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
            'supplier_id' => $this->supplier->id,
            'warehouse_id' => $this->warehouse->id,
            'code' => 'PI-RETURN-BASE',
            'issue_date' => '2026-11-01',
            'subtotal' => 1000,
            'tax_amount' => 150,
            'total' => 1150,
            'balance' => 1150,
            'status' => PurchaseInvoice::STATUS_OPEN,
        ]);

        PurchaseReturnInvoice::create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
            'purchase_invoice_id' => $invoice->id,
            'code' => 'PR-001',
            'return_date' => '2026-11-02',
            'subtotal' => 100,
            'tax_amount' => 15,
            'total' => 115,
        ]);

        PurchaseReturnInvoice::create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
            'purchase_invoice_id' => $invoice->id,
            'code' => 'PR-002',
            'return_date' => '2026-11-03',
            'subtotal' => 300,
            'tax_amount' => 45,
            'total' => 345,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('app.invoices.purchases.index', [
                'tab' => 'returns',
                'sort' => 'total',
                'direction' => 'asc',
            ]));

        $response->assertInertia(fn (Assert $page) => $page
            ->has('returns.data', 2)
            ->where('returns.data.0.code', 'PR-001')
            ->where('returns.data.1.code', 'PR-002')
        );
    }
}

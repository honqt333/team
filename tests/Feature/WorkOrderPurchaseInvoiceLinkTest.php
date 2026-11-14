<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Center;
use App\Models\Customer;
use App\Models\Dealer;
use App\Models\Invoice;
use App\Models\PurchaseInvoice;
use App\Models\Supplier;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\WorkOrder;
use App\Models\WorkOrderItem;
use App\Support\Permissions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class WorkOrderPurchaseInvoiceLinkTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;

    protected Center $center;

    protected User $user;

    protected WorkOrder $workOrder;

    protected PurchaseInvoice $purchaseInvoice;

    protected function setUp(): void
    {
        parent::setUp();

        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        foreach ([
            Permissions::WORK_ORDERS_VIEW,
            Permissions::WORK_ORDERS_UPDATE,
            Permissions::PURCHASE_INVOICES_VIEW,
            Permissions::INVOICES_VIEW,
        ] as $perm) {
            Permission::findOrCreate($perm, 'web');
        }

        $this->tenant = Tenant::create([
            'name' => 'Link Test Workshop',
            'slug' => 'link-test-workshop',
            'country' => 'SA',
            'currency' => 'SAR',
            'timezone' => 'Asia/Riyadh',
            'is_active' => true,
        ]);

        $this->center = Center::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Main Branch',
            'slug' => 'main-branch',
            'is_active' => true,
        ]);

        $this->user = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'current_center_id' => $this->center->id,
            'is_active' => true,
        ]);

        $this->user->centers()->attach($this->center->id, ['tenant_id' => $this->tenant->id]);
        app(PermissionRegistrar::class)->setPermissionsTeamId($this->tenant->id);
        $this->user->givePermissionTo([
            Permissions::WORK_ORDERS_VIEW,
            Permissions::WORK_ORDERS_UPDATE,
            Permissions::PURCHASE_INVOICES_VIEW,
            Permissions::INVOICES_VIEW,
        ]);

        $customer = Customer::create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
            'type' => 'individual',
            'name' => 'Ali Test',
            'phone' => '0500000011',
        ]);

        $vehicle = Vehicle::create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
            'customer_id' => $customer->id,
            'plate_number' => 'ABC 1234',
        ]);

        $this->workOrder = WorkOrder::create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'code' => 'WO-TEST-001',
            'status' => WorkOrder::STATUS_OPEN,
            'entry_date' => now()->toDateString(),
            'tax_enabled_snapshot' => false,
        ]);

        WorkOrderItem::create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
            'work_order_id' => $this->workOrder->id,
            'title' => 'Engine Repair',
            'qty' => 1,
            'unit_price' => 1150.00,
            'status' => WorkOrderItem::STATUS_IN_PROGRESS,
        ]);
        $this->workOrder->save();

        $supplier = Supplier::create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
            'name' => 'Auto Parts Supplier',
            'phone' => '0500000022',
            'is_active' => true,
        ]);

        $this->purchaseInvoice = PurchaseInvoice::create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
            'supplier_id' => $supplier->id,
            'invoice_number' => 'SUP-INV-100',
            'code' => 'PINV-0001',
            'issue_date' => now()->toDateString(),
            'status' => PurchaseInvoice::STATUS_OPEN,
            'subtotal' => 200.00,
            'tax_amount' => 30.00,
            'total' => 230.00,
            'balance' => 230.00,
        ]);
    }

    public function test_purchase_invoice_can_be_linked_and_unlinked_and_reflected_in_tickets_report(): void
    {
        $response = $this->actingAs($this->user)
            ->post(route('work-orders.purchase-invoices.link', $this->workOrder->id), [
                'purchase_invoice_id' => $this->purchaseInvoice->id,
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('purchase_invoices', [
            'id' => $this->purchaseInvoice->id,
            'work_order_id' => $this->workOrder->id,
        ]);

        // Reflected on WorkOrder show page
        $showResponse = $this->actingAs($this->user)
            ->get(route('work-orders.show', $this->workOrder->id));
        $showResponse->assertOk();
        $showResponse->assertInertia(fn ($page) => $page
            ->where('workOrder.purchase_invoices.0.id', $this->purchaseInvoice->id)
        );

        // Reflected in Tickets Report net profitability (1150 - 230 = 920)
        $reportResponse = $this->actingAs($this->user)
            ->get(route('app.reports.tickets', ['range' => 'all']));
        $reportResponse->assertOk();
        $reportResponse->assertInertia(fn ($page) => $page
            ->where('kpis.total_purchase_amount', 230)
            ->where('kpis.total_purchase_vat', 30)
            ->where('kpis.net_amount', 920)
        );

        // Unlink purchase invoice
        $unlinkResponse = $this->actingAs($this->user)
            ->delete(route('work-orders.purchase-invoices.unlink', [
                'workOrder' => $this->workOrder->id,
                'purchaseInvoice' => $this->purchaseInvoice->id,
            ]));

        $unlinkResponse->assertRedirect();
        $this->assertDatabaseHas('purchase_invoices', [
            'id' => $this->purchaseInvoice->id,
            'work_order_id' => null,
        ]);
    }

    public function test_cross_tenant_purchase_invoice_cannot_be_linked_to_work_order(): void
    {
        $otherTenant = Tenant::create([
            'name' => 'Other Workshop',
            'slug' => 'other-workshop',
            'country' => 'SA',
            'currency' => 'SAR',
            'timezone' => 'Asia/Riyadh',
            'is_active' => true,
        ]);
        $otherCenter = Center::create([
            'tenant_id' => $otherTenant->id,
            'name' => 'Other Branch',
            'slug' => 'other-branch',
            'is_active' => true,
        ]);
        $otherSupplier = Supplier::create([
            'tenant_id' => $otherTenant->id,
            'center_id' => $otherCenter->id,
            'name' => 'Other Supplier',
            'phone' => '0500000099',
            'is_active' => true,
        ]);
        $otherInvoice = PurchaseInvoice::create([
            'tenant_id' => $otherTenant->id,
            'center_id' => $otherCenter->id,
            'supplier_id' => $otherSupplier->id,
            'invoice_number' => 'SUP-999',
            'code' => 'PINV-9999',
            'issue_date' => now()->toDateString(),
            'status' => PurchaseInvoice::STATUS_OPEN,
            'subtotal' => 100.00,
            'tax_amount' => 15.00,
            'total' => 115.00,
            'balance' => 115.00,
        ]);

        $response = $this->actingAs($this->user)
            ->post(route('work-orders.purchase-invoices.link', $this->workOrder->id), [
                'purchase_invoice_id' => $otherInvoice->id,
            ]);

        $response->assertSessionHasErrors('purchase_invoice_id');
    }

    public function test_purchase_invoice_show_includes_linked_work_orders_tab_data(): void
    {
        $this->purchaseInvoice->update(['work_order_id' => $this->workOrder->id]);

        $response = $this->actingAs($this->user)
            ->get(route('app.invoices.purchases.show', $this->purchaseInvoice->id));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Purchasing/Invoices/Show')
            ->has('linkedWorkOrders', 1)
            ->where('linkedWorkOrders.0.work_order_id', $this->workOrder->id)
            ->where('linkedWorkOrders.0.code', 'WO-TEST-001')
            ->where('linkedWorkOrders.0.customer_name', 'Ali Test')
            ->where('linkedWorkOrders.0.plate_number', 'ABC 1234')
            ->where('linkedWorkOrders.0.allocated_amount', 230)
        );
    }

    public function test_purchase_invoice_source_type_distinguishes_warehouse_and_external_purchases(): void
    {
        $this->purchaseInvoice->update(['work_order_id' => $this->workOrder->id]);

        $warehouseInvoice = PurchaseInvoice::create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
            'supplier_id' => $this->purchaseInvoice->supplier_id,
            'work_order_id' => $this->workOrder->id,
            'source_type' => 'warehouse',
            'invoice_number' => 'SUP-WH-200',
            'code' => 'PINV-0002',
            'issue_date' => now()->toDateString(),
            'status' => PurchaseInvoice::STATUS_OPEN,
            'subtotal' => 500.00,
            'tax_amount' => 75.00,
            'total' => 575.00,
            'balance' => 575.00,
            'notes' => 'Auto-generated from GRN: GRN-0001',
        ]);

        $this->assertSame('external', $this->purchaseInvoice->fresh()->source_type);
        $this->assertSame('warehouse', $warehouseInvoice->fresh()->source_type);

        $showResponse = $this->actingAs($this->user)
            ->get(route('work-orders.show', $this->workOrder->id));

        $showResponse->assertOk();
        $showResponse->assertInertia(fn ($page) => $page
            ->has('workOrder.purchase_invoices', 2)
        );
    }

    public function test_link_purchase_invoice_accepts_optional_allocated_amount_and_work_order_show_exposes_invoices_and_dealer(): void
    {
        $dealer = Dealer::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Partner Fleet Co',
            'type' => 'fleet',
            'is_active' => true,
        ]);

        $this->workOrder->update([
            'dealer_id' => $dealer->id,
            'is_paid_by_dealer' => true,
        ]);

        $inv = Invoice::create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
            'work_order_id' => $this->workOrder->id,
            'customer_id' => $this->workOrder->customer_id,
            'dealer_id' => $dealer->id,
            'invoice_number' => 'I202620001',
            'type' => 'invoice',
            'status' => 'valid',
            'issue_date' => now()->toDateString(),
            'supply_date' => now()->toDateString(),
            'customer_name_snapshot' => 'Ali Test',
            'total_excl_tax' => 1000,
            'total_tax' => 150,
            'total_incl_tax' => 1150,
        ]);

        $cn = Invoice::create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
            'work_order_id' => $this->workOrder->id,
            'customer_id' => $this->workOrder->customer_id,
            'dealer_id' => $dealer->id,
            'original_invoice_id' => $inv->id,
            'invoice_number' => 'R202620001',
            'type' => 'credit_note',
            'status' => 'valid',
            'issue_date' => now()->toDateString(),
            'supply_date' => now()->toDateString(),
            'customer_name_snapshot' => 'Ali Test',
            'total_excl_tax' => 200,
            'total_tax' => 30,
            'total_incl_tax' => 230,
        ]);

        $linkResponse = $this->actingAs($this->user)
            ->post(route('work-orders.purchase-invoices.link', $this->workOrder->id), [
                'purchase_invoice_id' => $this->purchaseInvoice->id,
                'allocated_amount' => 150.00,
                'allocated_qty' => 1,
            ]);
        $linkResponse->assertRedirect();

        $showResponse = $this->actingAs($this->user)
            ->get(route('work-orders.show', $this->workOrder->id));

        $showResponse->assertOk();
        $showResponse->assertInertia(fn ($page) => $page
            ->where('workOrder.dealer.id', $dealer->id)
            ->where('workOrder.dealer.name', 'Partner Fleet Co')
            ->has('workOrder.invoices', 2)
            ->where('workOrder.invoice.invoice_number', 'I202620001')
            ->has('workOrder.credit_notes', 1)
            ->where('workOrder.credit_notes.0.invoice_number', 'R202620001')
        );
    }
}

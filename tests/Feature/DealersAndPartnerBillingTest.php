<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Actions\WorkOrder\CreateWorkOrderAction;
use App\Models\Center;
use App\Models\Customer;
use App\Models\Dealer;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\WorkOrder;
use App\Models\WorkOrderItem;
use App\Services\InvoiceService;
use App\Support\Permissions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class DealersAndPartnerBillingTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;

    protected Center $center;

    protected User $user;

    protected Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();

        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        foreach ([
            Permissions::CUSTOMERS_VIEW,
            Permissions::CUSTOMERS_CREATE,
            Permissions::CUSTOMERS_UPDATE,
            Permissions::CUSTOMERS_DELETE,
            Permissions::WORK_ORDERS_VIEW,
            Permissions::WORK_ORDERS_CREATE,
            Permissions::WORK_ORDERS_UPDATE,
            Permissions::INVOICES_VIEW,
            Permissions::INVOICES_CREATE,
        ] as $perm) {
            Permission::findOrCreate($perm, 'web');
        }

        $this->tenant = Tenant::create([
            'name' => 'Dealer Test Workshop',
            'slug' => 'dealer-test-workshop',
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
            Permissions::CUSTOMERS_VIEW,
            Permissions::CUSTOMERS_CREATE,
            Permissions::CUSTOMERS_UPDATE,
            Permissions::CUSTOMERS_DELETE,
            Permissions::WORK_ORDERS_VIEW,
            Permissions::WORK_ORDERS_CREATE,
            Permissions::WORK_ORDERS_UPDATE,
            Permissions::INVOICES_VIEW,
            Permissions::INVOICES_CREATE,
        ]);

        $this->customer = Customer::create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
            'type' => 'individual',
            'name' => 'Driver Ahmed',
            'phone' => '0500001122',
            'tax_number' => null,
            'city' => 'Jeddah',
        ]);
    }

    public function test_dealer_crud_toggle_and_delete_protection(): void
    {
        $this->actingAs($this->user)
            ->post(route('app.dealers.store'), [
                'name' => 'شركة التعاونية للتأمين',
                'name_en' => 'Tawuniya Insurance',
                'type' => Dealer::TYPE_INSURANCE,
                'contact_name' => 'Saleh Alharbi',
                'phone' => '0112223344',
                'tax_number' => '300011122200003',
                'credit_limit' => 50000,
                'payment_terms_days' => 45,
                'city' => 'Riyadh',
                'is_active' => true,
            ])
            ->assertRedirect();

        $dealer = Dealer::where('name', 'شركة التعاونية للتأمين')->firstOrFail();
        $this->assertSame($this->tenant->id, $dealer->tenant_id);
        $this->assertSame(Dealer::TYPE_INSURANCE, $dealer->type);

        $this->actingAs($this->user)
            ->get(route('app.dealers.index'))
            ->assertOk();

        $this->actingAs($this->user)
            ->get(route('app.dealers.show', $dealer))
            ->assertOk();

        $this->actingAs($this->user)
            ->put(route('app.dealers.update', $dealer), [
                'name' => 'شركة التعاونية المحدثة',
                'type' => Dealer::TYPE_INSURANCE,
                'tax_number' => '300011122200003',
                'payment_terms_days' => 60,
                'is_active' => true,
            ])
            ->assertRedirect();

        $this->assertSame('شركة التعاونية المحدثة', $dealer->fresh()->name);

        $this->actingAs($this->user)
            ->patch(route('app.dealers.toggle', $dealer))
            ->assertRedirect();

        $this->assertFalse($dealer->fresh()->is_active);

        $this->actingAs($this->user)
            ->delete(route('app.dealers.destroy', $dealer))
            ->assertRedirect(route('app.dealers.index'));

        $this->assertSoftDeleted('dealers', ['id' => $dealer->id]);
    }

    public function test_dealer_is_isolated_across_tenants(): void
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

        $foreignDealer = Dealer::create([
            'tenant_id' => $otherTenant->id,
            'center_id' => $otherCenter->id,
            'name' => 'Foreign Fleet Co',
            'type' => Dealer::TYPE_FLEET,
            'is_active' => true,
        ]);

        $this->actingAs($this->user)
            ->get(route('app.dealers.show', $foreignDealer))
            ->assertNotFound();

        $this->actingAs($this->user)
            ->post(route('vehicles.store'), [
                'customer_id' => $this->customer->id,
                'dealer_id' => $foreignDealer->id,
                'plate_number' => 'XYZ 9988',
                'make_other' => 'Toyota',
                'model_other' => 'Camry',
            ])
            ->assertSessionHasErrors('dealer_id');
    }

    public function test_work_order_inherits_vehicle_dealer_and_bills_dealer_on_invoice(): void
    {
        $dealer = Dealer::create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
            'name' => 'شركة أسطول النقل',
            'type' => Dealer::TYPE_FLEET,
            'tax_number' => '310123456700003',
            'city' => 'Riyadh',
            'district' => 'Olaya',
            'is_active' => true,
        ]);

        $vehicle = Vehicle::create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
            'customer_id' => $this->customer->id,
            'dealer_id' => $dealer->id,
            'plate_number' => 'KSA 2030',
        ]);

        $this->actingAs($this->user);

        // 1. Work order inherits vehicle.dealer_id automatically when dealer_id is omitted
        /** @var CreateWorkOrderAction $createAction */
        $createAction = app(CreateWorkOrderAction::class);

        $workOrderDealerBilled = $createAction->execute($this->user, [
            'customer_id' => $this->customer->id,
            'vehicle_id' => $vehicle->id,
            'is_paid_by_dealer' => true,
            'entry_date' => now()->toDateString(),
        ]);

        $this->assertSame($dealer->id, $workOrderDealerBilled->dealer_id);
        $this->assertTrue($workOrderDealerBilled->is_paid_by_dealer);

        // Ensure pricing_mode_snapshot is set for item calculation
        DB::table('work_orders')
            ->where('id', $workOrderDealerBilled->id)
            ->update([
                'tax_enabled_snapshot' => false,
                'tax_rate_snapshot' => 0,
                'total_excl_tax' => 1000,
                'total_tax' => 0,
                'total_incl_tax' => 1000,
            ]);

        WorkOrderItem::create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
            'work_order_id' => $workOrderDealerBilled->id,
            'title' => 'Fleet Periodic Service',
            'qty' => 1,
            'unit_price' => 1000.00,
            'status' => WorkOrderItem::STATUS_COMPLETED,
        ]);
        $workOrderDealerBilled->save();

        /** @var InvoiceService $invoiceService */
        $invoiceService = app(InvoiceService::class);
        $dealerInvoice = $invoiceService->createFromWorkOrder($workOrderDealerBilled->fresh(), $this->user);

        $this->assertSame($dealer->id, $dealerInvoice->dealer_id);
        $this->assertTrue($dealerInvoice->is_paid_by_dealer);
        $this->assertSame('شركة أسطول النقل', $dealerInvoice->customer_name_snapshot);
        $this->assertSame('310123456700003', $dealerInvoice->customer_vat_snapshot);
        $this->assertStringContainsString('Riyadh', (string) $dealerInvoice->customer_address_snapshot);

        // 2. When is_paid_by_dealer is false, invoice bills the individual customer snapshot
        $workOrderCustomerBilled = $createAction->execute($this->user, [
            'customer_id' => $this->customer->id,
            'vehicle_id' => $vehicle->id,
            'dealer_id' => $dealer->id,
            'is_paid_by_dealer' => false,
            'entry_date' => now()->toDateString(),
        ]);

        WorkOrderItem::create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
            'work_order_id' => $workOrderCustomerBilled->id,
            'title' => 'Extra Detailing',
            'qty' => 1,
            'unit_price' => 300.00,
            'status' => WorkOrderItem::STATUS_COMPLETED,
        ]);
        $workOrderCustomerBilled->save();

        $customerInvoice = $invoiceService->createFromWorkOrder($workOrderCustomerBilled->fresh(), $this->user);

        $this->assertSame($dealer->id, $customerInvoice->dealer_id);
        $this->assertFalse($customerInvoice->is_paid_by_dealer);
        $this->assertSame('Driver Ahmed', $customerInvoice->customer_name_snapshot);

        // 3. Partner cannot be deleted while linked to work orders/invoices
        $this->actingAs($this->user)
            ->delete(route('app.dealers.destroy', $dealer))
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertNotSoftDeleted('dealers', ['id' => $dealer->id]);
    }

    public function test_dealers_report_and_csv_export(): void
    {
        $dealer = Dealer::create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
            'name' => 'شركة تأجير الخليج',
            'type' => Dealer::TYPE_RENTAL,
            'tax_number' => '310999888700003',
            'is_active' => true,
        ]);

        $vehicle = Vehicle::create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
            'customer_id' => $this->customer->id,
            'dealer_id' => $dealer->id,
            'plate_number' => 'DLR 5050',
        ]);

        $wo = WorkOrder::create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
            'customer_id' => $this->customer->id,
            'vehicle_id' => $vehicle->id,
            'dealer_id' => $dealer->id,
            'is_paid_by_dealer' => true,
            'code' => 'WO-DLR-001',
            'status' => WorkOrder::STATUS_OPEN,
            'entry_date' => now()->toDateString(),
            'tax_enabled_snapshot' => false,
        ]);

        WorkOrderItem::create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
            'work_order_id' => $wo->id,
            'title' => 'Brake Service',
            'qty' => 1,
            'unit_price' => 500.00,
            'status' => WorkOrderItem::STATUS_COMPLETED,
        ]);
        $wo->save();

        $this->actingAs($this->user)
            ->get(route('app.reports.dealers'))
            ->assertOk();

        $exportResponse = $this->actingAs($this->user)
            ->get(route('app.reports.dealers.export'));

        $exportResponse->assertOk();
        $csv = $exportResponse->streamedContent();
        $this->assertStringContainsString('WO-DLR-001', $csv);
        $this->assertStringContainsString('شركة تأجير الخليج', $csv);
    }
}

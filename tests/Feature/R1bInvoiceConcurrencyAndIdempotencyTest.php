<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\PaymentType;
use App\Events\Payment\PaymentRecorded;
use App\Listeners\Payment\HandleAutoInvoiceOnDoneWorkOrder;
use App\Models\Center;
use App\Models\CenterSequence;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\WorkOrder;
use App\Models\WorkOrderItem;
use App\Services\InvoiceService;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class R1bInvoiceConcurrencyAndIdempotencyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        foreach ([
            'crm.work_orders.view',
            'crm.work_orders.update',
            'crm.payments.create',
            'crm.invoices.create',
            'crm.invoices.view',
        ] as $permission) {
            Permission::findOrCreate($permission, 'web');
        }
    }

    protected function createTenantContext(): array
    {
        $tenant = Tenant::factory()->create();
        $center = Center::factory()->create(['tenant_id' => $tenant->id]);

        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'current_center_id' => $center->id,
        ]);
        $user->centers()->attach($center->id, ['tenant_id' => $tenant->id]);

        app(PermissionRegistrar::class)->setPermissionsTeamId($tenant->id);
        $user->givePermissionTo([
            'crm.work_orders.view',
            'crm.work_orders.update',
            'crm.payments.create',
            'crm.invoices.create',
            'crm.invoices.view',
        ]);

        $customer = Customer::create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'type' => 'individual',
            'name' => 'R1B Customer',
            'phone' => '050'.random_int(1000000, 9999999),
        ]);

        $vehicle = Vehicle::create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'customer_id' => $customer->id,
            'plate_number' => strtoupper(Str::random(3)).' '.random_int(1000, 9999),
        ]);

        $user->refresh();

        return [$tenant, $center, $user, $customer, $vehicle];
    }

    protected function createDoneWorkOrder(Tenant $tenant, Center $center, Customer $customer, Vehicle $vehicle, float $price = 300.0): WorkOrder
    {
        $workOrder = WorkOrder::create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'code' => 'WO-'.strtoupper(Str::random(6)),
            'status' => WorkOrder::STATUS_DONE,
        ]);

        WorkOrderItem::create([
            'work_order_id' => $workOrder->id,
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'title' => 'Oil & Filter Service',
            'qty' => 1,
            'unit_price' => $price,
            'is_taxable' => false,
            'tax_rate_snapshot' => 0,
            'status' => WorkOrderItem::STATUS_COMPLETED,
        ]);

        $workOrder->recalculateTotals();
        $workOrder->refresh();

        return $workOrder;
    }

    public function test_first_invoice_creation_succeeds_and_duplicate_request_is_blocked(): void
    {
        [$tenant, $center, $user, $customer, $vehicle] = $this->createTenantContext();
        $this->actingAs($user);

        $workOrder = $this->createDoneWorkOrder($tenant, $center, $customer, $vehicle);
        $service = app(InvoiceService::class);

        $invoice = $service->createFromWorkOrder($workOrder, $user);
        $service->issueInvoice($invoice);

        $this->assertEquals(1, Invoice::where('work_order_id', $workOrder->id)->where('type', 'invoice')->count());

        // Second direct call must throw and not create a duplicate or advance sequence
        $seqBefore = CenterSequence::where('tenant_id', $tenant->id)
            ->where('center_id', $center->id)
            ->where('type', 'invoice')
            ->value('current_value');

        try {
            $service->createFromWorkOrder($workOrder->fresh(), $user);
            $this->fail('Expected Exception for duplicate work-order invoice creation.');
        } catch (Exception $e) {
            $this->assertSame(__('invoices.already_exists'), $e->getMessage());
        }

        $this->assertEquals(1, Invoice::where('work_order_id', $workOrder->id)->where('type', 'invoice')->count());
        $seqAfter = CenterSequence::where('tenant_id', $tenant->id)
            ->where('center_id', $center->id)
            ->where('type', 'invoice')
            ->value('current_value');
        $this->assertSame(1, (int) $seqBefore);
        $this->assertSame($seqBefore, $seqAfter);
    }

    public function test_stale_work_order_instance_cannot_create_duplicate_invoice(): void
    {
        [$tenant, $center, $user, $customer, $vehicle] = $this->createTenantContext();
        $this->actingAs($user);

        $workOrder = $this->createDoneWorkOrder($tenant, $center, $customer, $vehicle);
        $staleWorkOrder = WorkOrder::findOrFail($workOrder->id);

        $service = app(InvoiceService::class);
        $first = $service->createFromWorkOrder($workOrder, $user);
        $service->issueInvoice($first);

        // Pass stale instance (with unloaded/null cached invoice relation)
        $this->expectException(Exception::class);
        $this->expectExceptionMessage(__('invoices.already_exists'));

        $service->createFromWorkOrder($staleWorkOrder, $user);
    }

    public function test_auto_invoicing_and_manual_creation_do_not_duplicate(): void
    {
        [$tenant, $center, $user, $customer, $vehicle] = $this->createTenantContext();
        $this->actingAs($user);

        // Case A: Manual creation first, then PaymentRecorded listener with stale WorkOrder
        $woA = $this->createDoneWorkOrder($tenant, $center, $customer, $vehicle, 200);
        $staleWoA = WorkOrder::findOrFail($woA->id);

        $manualInvoice = app(InvoiceService::class)->createFromWorkOrder($woA, $user);
        app(InvoiceService::class)->issueInvoice($manualInvoice);

        $paymentA = Payment::create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'work_order_id' => $woA->id,
            'type' => PaymentType::PAYMENT,
            'payment_method' => 'cash',
            'amount' => 200,
            'payment_date' => now()->toDateString(),
            'created_by' => $user->id,
            'received_by' => $user->id,
        ]);

        // Set stale relation on payment so HandleAutoInvoiceOnDoneWorkOrder sees stale WO
        $paymentA->setRelation('workOrder', $staleWoA);
        app(HandleAutoInvoiceOnDoneWorkOrder::class)->handle(new PaymentRecorded($paymentA));

        $this->assertEquals(1, Invoice::where('work_order_id', $woA->id)->where('type', 'invoice')->count());

        // Case B: Auto-invoicing first, then manual creation attempt (via service and controller on completed WO)
        $woB = $this->createDoneWorkOrder($tenant, $center, $customer, $vehicle, 250);
        $paymentB = Payment::create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'work_order_id' => $woB->id,
            'type' => PaymentType::PAYMENT,
            'payment_method' => 'cash',
            'amount' => 250,
            'payment_date' => now()->toDateString(),
            'created_by' => $user->id,
            'received_by' => $user->id,
        ]);

        app(HandleAutoInvoiceOnDoneWorkOrder::class)->handle(new PaymentRecorded($paymentB));
        $this->assertEquals(1, Invoice::where('work_order_id', $woB->id)->where('type', 'invoice')->count());

        // Attempting manual creation after auto-invoicing must be rejected
        try {
            app(InvoiceService::class)->createFromWorkOrder($woB->fresh(), $user);
            $this->fail('Expected Exception when creating manual invoice after auto-invoicing.');
        } catch (Exception $e) {
            $this->assertSame(__('invoices.already_exists'), $e->getMessage());
        }

        $this->assertEquals(1, Invoice::where('work_order_id', $woB->id)->where('type', 'invoice')->count());
    }

    public function test_multiple_credit_notes_are_allowed_for_same_work_order(): void
    {
        [$tenant, $center, $user, $customer, $vehicle] = $this->createTenantContext();
        $this->actingAs($user);

        $workOrder = $this->createDoneWorkOrder($tenant, $center, $customer, $vehicle, 500);
        $service = app(InvoiceService::class);
        $invoice = $service->createFromWorkOrder($workOrder, $user);
        $service->issueInvoice($invoice);

        $item = $workOrder->items()->firstOrFail();

        $this->postJson(route('app.work-orders.credit-note.store', $workOrder->id), [
            'services' => [['id' => $item->id, 'return_amount' => 100.00]],
            'parts' => [],
            'reason' => 'Partial adjustment 1',
            'record_refund' => false,
        ])->assertOk();

        $this->postJson(route('app.work-orders.credit-note.store', $workOrder->id), [
            'services' => [['id' => $item->id, 'return_amount' => 150.00]],
            'parts' => [],
            'reason' => 'Partial adjustment 2',
            'record_refund' => false,
        ])->assertOk();

        $this->assertEquals(2, Invoice::where('work_order_id', $workOrder->id)->where('type', 'credit_note')->count());
        $this->assertEquals(1, Invoice::where('work_order_id', $workOrder->id)->where('type', 'invoice')->count());
    }

    public function test_tenant_isolation_and_unique_invoice_numbers_are_preserved(): void
    {
        [$tenantA, $centerA, $userA, $customerA, $vehicleA] = $this->createTenantContext();
        [$tenantB, $centerB, $userB, $customerB, $vehicleB] = $this->createTenantContext();

        $woA1 = $this->createDoneWorkOrder($tenantA, $centerA, $customerA, $vehicleA, 100);
        $woA2 = $this->createDoneWorkOrder($tenantA, $centerA, $customerA, $vehicleA, 150);
        $woB1 = $this->createDoneWorkOrder($tenantB, $centerB, $customerB, $vehicleB, 200);

        $service = app(InvoiceService::class);

        $this->actingAs($userA);
        $invA1 = $service->createFromWorkOrder($woA1, $userA);
        $service->issueInvoice($invA1);

        $invA2 = $service->createFromWorkOrder($woA2, $userA);
        $service->issueInvoice($invA2);

        $this->assertNotSame($invA1->invoice_number, $invA2->invoice_number);

        // Tenant B user cannot create invoice for Tenant A work order
        $this->actingAs($userB);
        $this->post(route('app.work-orders.invoice', $woA1))->assertNotFound();

        // Tenant B can create its own invoice independently
        $invB1 = $service->createFromWorkOrder($woB1, $userB);
        $service->issueInvoice($invB1);

        $this->assertEquals(1, Invoice::withoutGlobalScopes()->where('work_order_id', $woB1->id)->where('type', 'invoice')->count());
        $this->assertEquals(2, Invoice::withoutGlobalScopes()->where('tenant_id', $tenantA->id)->where('type', 'invoice')->count());
    }
}

<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Http\Requests\App\Print\UploadSignatureRequest;
use App\Models\Center;
use App\Models\CompanyTransaction;
use App\Models\Customer;
use App\Models\Dealer;
use App\Models\HR\Employee;
use App\Models\HR\Leave;
use App\Models\IncomeCategory;
use App\Models\Invoice;
use App\Models\PurchaseInvoice;
use App\Models\PurchaseInvoiceLine;
use App\Models\Supplier;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\WorkOrder;
use App\Models\WorkOrderItem;
use App\Support\Permissions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Validator;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class AuditRegressionVerificationTest extends TestCase
{
    use RefreshDatabase;

    private function createTenantStack(string $suffix = 'A'): array
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $tenant = Tenant::create([
            'name' => "Tenant {$suffix}",
            'slug' => 'tenant-'.strtolower($suffix).'-'.uniqid(),
            'country' => 'SA',
            'currency' => 'SAR',
            'timezone' => 'Asia/Riyadh',
            'is_active' => true,
        ]);

        $center = Center::create([
            'tenant_id' => $tenant->id,
            'name' => "Center {$suffix}",
            'slug' => 'center-'.strtolower($suffix).'-'.uniqid(),
            'is_active' => true,
        ]);

        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'current_center_id' => $center->id,
            'is_active' => true,
        ]);
        $user->centers()->attach($center->id, ['tenant_id' => $tenant->id]);

        return [$tenant, $center, $user];
    }

    public function test_c2_company_profile_does_not_leak_other_tenant_company_level_invoices(): void
    {
        [$tenantA, $centerA, $userA] = $this->createTenantStack('A');
        [$tenantB, $centerB, $userB] = $this->createTenantStack('B');

        Permission::findOrCreate('settings.company.manage', 'web');
        app(PermissionRegistrar::class)->setPermissionsTeamId($tenantA->id);
        $userA->givePermissionTo('settings.company.manage');

        $customerB = Customer::withoutGlobalScopes()->create([
            'tenant_id' => $tenantB->id,
            'center_id' => $centerB->id,
            'type' => 'individual',
            'name' => 'Secret Customer B',
            'phone' => '0559999999',
        ]);

        $invoiceB = new Invoice([
            'tenant_id' => $tenantB->id,
            'center_id' => null,
            'customer_id' => $customerB->id,
            'invoice_number' => 'INV-SECRET-B',
            'type' => 'invoice',
            'subtype' => 'simplified',
            'issue_date' => now(),
            'supply_date' => now()->toDateString(),
            'total_excl_tax' => 1000,
            'total_tax' => 150,
            'total_incl_tax' => 1150,
            'total_taxable_amount' => 1000,
            'total_paid' => 0,
            'status' => 'issued',
            'payment_status' => 'unpaid',
        ]);
        $invoiceB->saveQuietly();

        $catB = IncomeCategory::withoutGlobalScopes()->create([
            'tenant_id' => $tenantB->id,
            'name_ar' => 'مبيعات',
            'name_en' => 'Sales',
            'transaction_type' => 'income',
            'is_taxable' => true,
            'is_active' => true,
        ]);

        CompanyTransaction::withoutGlobalScopes()->create([
            'tenant_id' => $tenantB->id,
            'center_id' => null,
            'title' => 'Secret company sale B',
            'income_category_id' => $catB->id,
            'invoice_id' => $invoiceB->id,
            'transaction_type' => 'income',
            'amount' => 1000,
            'tax_amount' => 150,
            'total_amount' => 1150,
            'transaction_date' => now()->toDateString(),
            'status' => 'approved',
        ]);

        $response = $this->actingAs($userA)->get(route('settings.company'));
        $response->assertOk();

        $props = $response->viewData('page')['props'] ?? [];
        $companyInvoices = $props['company_invoices'] ?? [];

        if (is_callable($companyInvoices)) {
            $companyInvoices = $companyInvoices();
        }
        $salesNumbers = collect($companyInvoices['sales'] ?? [])->pluck('invoice_number')->all();
        $this->assertNotContains('INV-SECRET-B', $salesNumbers);
    }

    public function test_a1_a6_center_scoped_enforces_tenant_id_even_when_center_is_null_or_scope_removed(): void
    {
        [$tenantA, $centerA, $userA] = $this->createTenantStack('A');
        [$tenantB, $centerB] = $this->createTenantStack('B');

        Customer::withoutGlobalScopes()->create([
            'tenant_id' => $tenantA->id,
            'center_id' => $centerA->id,
            'type' => 'individual',
            'name' => 'Customer A',
            'phone' => '0500000001',
        ]);

        Customer::withoutGlobalScopes()->create([
            'tenant_id' => $tenantB->id,
            'center_id' => $centerB->id,
            'type' => 'individual',
            'name' => 'Customer B',
            'phone' => '0500000002',
        ]);

        // 1. When current_center_id is null on the authenticated user, tenant_scoped must still filter
        $userA->forceFill(['current_center_id' => null])->save();
        $this->actingAs($userA->fresh());
        $namesWithNullCenter = Customer::pluck('name')->all();
        $this->assertSame(['Customer A'], $namesWithNullCenter);

        // 2. When withoutGlobalScope('center_scoped') is called, tenant_scoped must still filter
        $userA->forceFill(['current_center_id' => $centerA->id])->save();
        $this->actingAs($userA->fresh());
        $namesWithoutCenterScope = Customer::withoutGlobalScope('center_scoped')->pluck('name')->all();
        $this->assertSame(['Customer A'], $namesWithoutCenterScope);
    }

    public function test_a4_upload_signature_request_rejects_svg_with_script_after_80kb_padding(): void
    {
        $padding = str_repeat(' ', 80 * 1024);
        $maliciousSvg = '<svg xmlns="http://www.w3.org/2000/svg">'.$padding.'<script>alert(1)</script></svg>';
        $file = UploadedFile::fake()->createWithContent('sig.svg', $maliciousSvg);

        $request = new UploadSignatureRequest;
        $validator = Validator::make(['file' => $file], $request->rules());

        $this->assertTrue($validator->fails(), 'Padded SVG with <script> after 80KB must be rejected.');
    }

    public function test_b1_dealer_outstanding_balance_subtracts_credit_notes(): void
    {
        [$tenant, $center, $user] = $this->createTenantStack('D');
        $this->actingAs($user);

        $dealer = Dealer::create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'name' => 'Test Dealer',
            'credit_limit' => 10000,
            'is_active' => true,
        ]);

        $customer = Customer::create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'type' => 'individual',
            'name' => 'Dealer Customer',
            'phone' => '0507778889',
        ]);

        $taxInv = new Invoice([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'customer_id' => $customer->id,
            'dealer_id' => $dealer->id,
            'invoice_number' => 'INV-D1',
            'type' => 'invoice',
            'subtype' => 'simplified',
            'issue_date' => now(),
            'supply_date' => now()->toDateString(),
            'total_excl_tax' => 1000,
            'total_tax' => 150,
            'total_incl_tax' => 1150,
            'total_taxable_amount' => 1000,
            'total_paid' => 150,
            'status' => 'issued',
            'payment_status' => 'partially_paid',
        ]);
        $taxInv->saveQuietly();

        $creditNote = new Invoice([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'customer_id' => $customer->id,
            'dealer_id' => $dealer->id,
            'invoice_number' => 'CN-D1',
            'type' => 'credit_note',
            'subtype' => 'simplified',
            'issue_date' => now(),
            'supply_date' => now()->toDateString(),
            'total_excl_tax' => 200,
            'total_tax' => 30,
            'total_incl_tax' => 230,
            'total_taxable_amount' => 200,
            'total_paid' => 0,
            'status' => 'issued',
            'payment_status' => 'unpaid',
        ]);
        $creditNote->saveQuietly();

        // Expected balance: (1150 - 150) - 230 = 770
        $this->assertEqualsWithDelta(770.0, $dealer->getOutstandingBalance(), 0.01);
    }

    public function test_b2_b3_quick_purchase_invoice_computes_server_tax_creates_lines_and_blocks_overallocation(): void
    {
        [$tenant, $center, $user] = $this->createTenantStack('P');
        $this->actingAs($user);

        foreach ([
            Permissions::WORK_ORDERS_VIEW,
            Permissions::WORK_ORDERS_UPDATE,
            Permissions::PURCHASE_INVOICES_VIEW,
            Permissions::INVOICES_VIEW,
        ] as $perm) {
            Permission::findOrCreate($perm, 'web');
        }
        app(PermissionRegistrar::class)->setPermissionsTeamId($tenant->id);
        $user->givePermissionTo([
            Permissions::WORK_ORDERS_VIEW,
            Permissions::WORK_ORDERS_UPDATE,
            Permissions::PURCHASE_INVOICES_VIEW,
            Permissions::INVOICES_VIEW,
        ]);

        $customer = Customer::create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'type' => 'individual',
            'name' => 'WO Customer',
            'phone' => '0501112233',
        ]);
        $vehicle = Vehicle::create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'customer_id' => $customer->id,
            'plate_number' => 'ABC 1234',
        ]);
        $workOrder = WorkOrder::create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'code' => 'WO-1001',
            'status' => WorkOrder::STATUS_OPEN,
            'entry_date' => now()->toDateString(),
        ]);
        $workOrderItem = WorkOrderItem::create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'work_order_id' => $workOrder->id,
            'title' => 'Brake Service',
            'qty' => 1,
            'unit_price' => 400.00,
        ]);
        $supplier = Supplier::create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'name' => 'Quick Supplier',
            'is_active' => true,
        ]);

        // Client attempts to pass tax_amount = 0 to evade VAT
        $response = $this->postJson(route('app.work-orders.quick-purchase-invoice.store', $workOrder), [
            'supplier_id' => $supplier->id,
            'invoice_number' => 'SUP-INV-99',
            'issue_date' => now()->toDateString(),
            'type' => 'external_part',
            'lines' => [
                [
                    'item_description' => 'Brake Pads',
                    'allocated_qty' => 2,
                    'unit_cost' => 100.00,
                    'tax_amount' => 0.00,
                    'total_cost' => 200.00,
                    'work_order_item_id' => $workOrderItem->id,
                ],
            ],
        ]);

        $response->assertStatus(201);

        $pi = PurchaseInvoice::where('invoice_number', 'SUP-INV-99')->firstOrFail();
        // Subtotal = 200, server-computed 15% VAT = 30, total = 230
        $this->assertEqualsWithDelta(200.0, (float) $pi->subtotal, 0.01);
        $this->assertEqualsWithDelta(30.0, (float) $pi->tax_amount, 0.01);
        $this->assertEqualsWithDelta(230.0, (float) $pi->total, 0.01);

        // PurchaseInvoiceLine must be created
        $line = PurchaseInvoiceLine::where('purchase_invoice_id', $pi->id)->first();
        $this->assertNotNull($line, 'PurchaseInvoiceLine must be created for quick purchase invoice.');
        $this->assertEqualsWithDelta(2.0, (float) $line->qty, 0.01);

        // B2: Attempting to allocate more from the already-fully-allocated line must fail validation
        $overAllocResponse = $this->postJson(route('app.work-orders.purchase-allocations.store', $workOrder), [
            'purchase_invoice_id' => $pi->id,
            'purchase_invoice_line_id' => $line->id,
            'type' => 'external_part',
            'item_description' => 'Extra Brake Pads',
            'allocated_qty' => 1,
            'unit_cost' => 100.00,
        ]);

        $overAllocResponse->assertStatus(422);
        $overAllocResponse->assertJsonValidationErrors('allocated_qty');
    }

    public function test_l1_l2_mass_assignment_protection_on_existing_user_and_invoice(): void
    {
        [$tenantA, $centerA, $userA] = $this->createTenantStack('A');
        [$tenantB, $centerB] = $this->createTenantStack('B');

        $userA->fill([
            'name' => 'Updated Name',
            'tenant_id' => $tenantB->id,
            'current_center_id' => $centerB->id,
        ])->save();

        $userA->refresh();
        $this->assertSame('Updated Name', $userA->name);
        $this->assertSame($tenantA->id, $userA->tenant_id);
        $this->assertSame($centerA->id, $userA->current_center_id);

        $customer = Customer::withoutGlobalScopes()->create([
            'tenant_id' => $tenantA->id,
            'center_id' => $centerA->id,
            'type' => 'individual',
            'name' => 'Inv Cust',
            'phone' => '0509988776',
        ]);

        $invoice = new Invoice([
            'tenant_id' => $tenantA->id,
            'center_id' => $centerA->id,
            'customer_id' => $customer->id,
            'invoice_number' => 'INV-MASS-1',
            'type' => 'invoice',
            'subtype' => 'simplified',
            'issue_date' => now(),
            'supply_date' => now()->toDateString(),
            'total_excl_tax' => 100,
            'total_tax' => 15,
            'total_incl_tax' => 115,
            'total_taxable_amount' => 100,
            'total_paid' => 0,
            'status' => 'draft',
            'payment_status' => 'unpaid',
        ]);
        $invoice->saveQuietly();

        // Attempting mass-assignment on existing Invoice must ignore status, payment_status, and financial totals
        $invoice->fill([
            'notes' => 'Safe note',
            'status' => 'issued',
            'payment_status' => 'paid',
            'total_excl_tax' => 9999,
            'total_tax' => 999,
            'total_incl_tax' => 10998,
            'total_taxable_amount' => 9999,
            'total_paid' => 10998,
        ])->saveQuietly();

        $invoice->refresh();
        $this->assertSame('Safe note', $invoice->notes);
        $this->assertSame('draft', $invoice->status);
        $this->assertSame('unpaid', $invoice->payment_status);
        $this->assertEqualsWithDelta(100.0, (float) $invoice->total_excl_tax, 0.01);
        $this->assertEqualsWithDelta(15.0, (float) $invoice->total_tax, 0.01);
        $this->assertEqualsWithDelta(115.0, (float) $invoice->total_incl_tax, 0.01);
        $this->assertEqualsWithDelta(0.0, (float) $invoice->total_paid, 0.01);
    }

    public function test_leave_controller_blocks_self_approval(): void
    {
        [$tenant, $center, $user] = $this->createTenantStack('L');
        $this->actingAs($user);

        Permission::findOrCreate(Permissions::HR_LEAVES_APPROVE, 'web');
        app(PermissionRegistrar::class)->setPermissionsTeamId($tenant->id);
        $user->givePermissionTo(Permissions::HR_LEAVES_APPROVE);

        $employee = Employee::create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'user_id' => $user->id,
            'name_ar' => 'موظف يوافق لنفسه',
            'name_en' => 'Self Approver',
            'status' => 'active',
        ]);

        $leave = Leave::create([
            'tenant_id' => $tenant->id,
            'employee_id' => $employee->id,
            'type' => 'annual',
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDays(2)->toDateString(),
            'days' => 3,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($user)->put(route('app.hr.leaves.update-status', $leave), [
            'status' => 'approved',
        ]);
        $response->assertForbidden();
    }
}

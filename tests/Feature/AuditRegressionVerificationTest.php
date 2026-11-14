<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Http\Requests\App\Print\UploadSignatureRequest;
use App\Models\Center;
use App\Models\CompanyTransaction;
use App\Models\Customer;
use App\Models\Dealer;
use App\Models\HR\Allowance;
use App\Models\HR\Attendance;
use App\Models\HR\AttendanceCorrectionRequest;
use App\Models\HR\AttendanceSettings;
use App\Models\HR\Employee;
use App\Models\HR\EmployeeOffboarding;
use App\Models\HR\JobTitle;
use App\Models\HR\Leave;
use App\Models\HR\PayrollItem;
use App\Models\HR\PayrollRun;
use App\Models\IncomeCategory;
use App\Models\Invoice;
use App\Models\Nationality;
use App\Models\PurchaseInvoice;
use App\Models\PurchaseInvoiceLine;
use App\Models\Role;
use App\Models\Supplier;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\WorkOrder;
use App\Models\WorkOrderItem;
use App\Services\HR\EndOfServiceCalculator;
use App\Services\HR\PayrollAccountingService;
use App\Services\HR\TechnicianCommissionService;
use App\Support\Permissions;
use App\Support\TenancyContext;
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

    public function test_p0_1_offboarding_uses_employee_record_salary_by_default_and_logs_manual_override(): void
    {
        [$tenant, $center, $user] = $this->createTenantStack('P01');
        $this->actingAs($user);

        Permission::findOrCreate(Permissions::HR_EMPLOYEES_UPDATE, 'web');
        app(PermissionRegistrar::class)->setPermissionsTeamId($tenant->id);
        $user->givePermissionTo(Permissions::HR_EMPLOYEES_UPDATE);

        $employee = Employee::create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'name_ar' => 'موظف تسوية',
            'name_en' => 'Settlement Employee',
            'hire_date' => '2021-01-01',
            'base_salary' => 10000,
            'housing_allowance' => 0,
            'status' => 'active',
        ]);

        $transportAllowance = Allowance::create([
            'tenant_id' => $tenant->id,
            'name_ar' => 'بدل نقل',
            'name_en' => 'Transport Allowance',
            'type' => 'fixed',
            'amount' => 800,
            'is_housing' => false,
            'is_active' => true,
        ]);
        $employee->allowances()->attach($transportAllowance->id, ['custom_amount' => 800]);

        // 1. Employee without housing allowance => calculation_salary = base_salary (10,000), NOT base + all allowances (10,800)
        // Also tamper with base_salary=1 without use_manual_salary => ignored
        $response = $this->actingAs($user)->post(route('app.hr.employees.offboarding.store', $employee), [
            'termination_type' => EmployeeOffboarding::TYPE_EMPLOYER_TERMINATION,
            'hire_date' => '2021-01-01',
            'termination_date' => '2026-01-01',
            'base_salary' => 1,
            'allowances_amount' => 0,
            'unused_leave_days' => 0,
        ]);
        $response->assertRedirect();
        $response->assertSessionHasNoErrors();

        $offboarding = EmployeeOffboarding::where('employee_id', $employee->id)->latest('id')->firstOrFail();
        $this->assertFalse($offboarding->use_manual_salary);
        $this->assertSame(10000.0, (float) $offboarding->base_salary);
        $this->assertSame(800.0, (float) $offboarding->allowances_amount);
        $this->assertSame(0.0, (float) $offboarding->housing_allowance_amount);
        $this->assertSame(10000.0, (float) $offboarding->calculation_salary);

        // 2. Attach housing allowance of 500 => calculation_salary = base (10,000) + housing (500) = 10,500
        $housingAllowance = Allowance::create([
            'tenant_id' => $tenant->id,
            'name_ar' => 'بدل سكن',
            'name_en' => 'Housing Allowance',
            'type' => 'fixed',
            'amount' => 500,
            'is_housing' => true,
            'is_active' => true,
        ]);
        $employee->allowances()->attach($housingAllowance->id, ['custom_amount' => 500]);

        $responseWithHousing = $this->actingAs($user)->post(route('app.hr.employees.offboarding.store', $employee), [
            'termination_type' => EmployeeOffboarding::TYPE_EMPLOYER_TERMINATION,
            'hire_date' => '2021-01-01',
            'termination_date' => '2026-01-01',
            'unused_leave_days' => 0,
        ]);
        $responseWithHousing->assertRedirect();
        $offboarding->refresh();
        $this->assertSame(500.0, (float) $offboarding->housing_allowance_amount);
        $this->assertSame(10500.0, (float) $offboarding->calculation_salary);

        // 3. Manual override without manual_salary_reason is rejected with 422
        $missingReasonResponse = $this->actingAs($user)->post(route('app.hr.employees.offboarding.store', $employee), [
            'termination_type' => EmployeeOffboarding::TYPE_EMPLOYER_TERMINATION,
            'hire_date' => '2021-01-01',
            'termination_date' => '2026-01-01',
            'use_manual_salary' => true,
            'base_salary' => 9000,
            'allowances_amount' => 1500,
            'housing_allowance_amount' => 1500,
        ]);
        $missingReasonResponse->assertSessionHasErrors('manual_salary_reason');

        // 4. Explicit manual override with manual_salary_reason succeeds and pins original calculated values
        $overrideResponse = $this->actingAs($user)->post(route('app.hr.employees.offboarding.store', $employee), [
            'termination_type' => EmployeeOffboarding::TYPE_EMPLOYER_TERMINATION,
            'hire_date' => '2021-01-01',
            'termination_date' => '2026-01-01',
            'use_manual_salary' => true,
            'manual_salary_reason' => 'تسوية خاصة بموجب اتفاقية إخلاء الطرف',
            'base_salary' => 9000,
            'allowances_amount' => 1500,
            'housing_allowance_amount' => 1500,
        ]);
        $overrideResponse->assertRedirect();
        $overrideResponse->assertSessionHasNoErrors();

        $offboarding->refresh();
        $this->assertTrue($offboarding->use_manual_salary);
        $this->assertSame('تسوية خاصة بموجب اتفاقية إخلاء الطرف', $offboarding->manual_salary_reason);
        $this->assertSame(10000.0, (float) $offboarding->calculated_base_salary);
        $this->assertSame(800.0, (float) $offboarding->calculated_allowances_amount);
        $this->assertSame(9000.0, (float) $offboarding->base_salary);
        $this->assertSame(10500.0, (float) $offboarding->calculation_salary);
    }

    public function test_p0_2_eosb_calculator_supports_article_87_repatriation_and_statutory_compensation(): void
    {
        $calculator = app(EndOfServiceCalculator::class);

        // Resignation under 2 years normally gets 0%, but with Article 87 (force majeure/marriage/childbirth) gets 100%
        $art87 = $calculator->calculate([
            'termination_type' => EmployeeOffboarding::TYPE_RESIGNATION,
            'is_article_87' => true,
            'hire_date' => '2025-01-01',
            'termination_date' => '2026-01-01', // 365 days = 1.0 year
            'base_salary' => 8000,
            'allowances_amount' => 3000,
            'housing_allowance_amount' => 2000, // calculation_salary = 10,000
            'repatriation_ticket_amount' => 1800,
            'include_statutory_compensation' => true, // 15 days' wage per year = 5,000
        ]);

        $this->assertTrue($art87['is_article_87']);
        $this->assertSame(10000.0, $art87['calculation_salary']);
        $this->assertSame(1.0, $art87['eosb_multiplier']);
        $this->assertSame(5000.0, $art87['eosb_amount']);
        $this->assertSame(1800.0, $art87['repatriation_ticket_amount']);
        $this->assertSame(5000.0, $art87['statutory_compensation_amount']);
        $this->assertSame(11800.0, $art87['net_settlement']);
    }

    public function test_p1_3_attendance_center_isolation_blocks_cross_branch_update_and_delete(): void
    {
        [$tenant, $centerA, $userA] = $this->createTenantStack('P13');
        $centerB = Center::create([
            'tenant_id' => $tenant->id,
            'name' => 'Center B',
            'slug' => 'center-b-'.uniqid(),
            'is_active' => true,
        ]);

        Permission::findOrCreate('hr.attendance.view', 'web');
        Permission::findOrCreate('hr.attendance.manage', 'web');
        app(PermissionRegistrar::class)->setPermissionsTeamId($tenant->id);
        $userA->givePermissionTo(['hr.attendance.view', 'hr.attendance.manage']);

        $employeeB = Employee::create([
            'tenant_id' => $tenant->id,
            'center_id' => $centerB->id,
            'name_ar' => 'موظف فرع ب',
            'name_en' => 'Branch B Employee',
            'status' => 'active',
        ]);

        $attendanceB = Attendance::withoutGlobalScopes()->create([
            'tenant_id' => $tenant->id,
            'center_id' => $centerB->id,
            'employee_id' => $employeeB->id,
            'date' => '2026-04-10',
            'status' => 'present',
            'check_in' => '08:00',
        ]);

        // Branch A user tries to mark Branch B employee as absent
        $putResponse = $this->actingAs($userA)->put(route('app.hr.attendance.update', $attendanceB->id), [
            'status' => 'absent',
        ]);
        $putResponse->assertForbidden();

        // Branch A user tries to delete Branch B attendance
        $deleteResponse = $this->actingAs($userA)->delete(route('app.hr.attendance.destroy', $attendanceB->id));
        $deleteResponse->assertForbidden();

        $this->assertDatabaseHas('hr_attendances', [
            'id' => $attendanceB->id,
            'status' => 'present',
            'deleted_at' => null,
        ]);
    }

    public function test_p1_4_geofence_audit_trail_persists_coordinates_low_confidence_and_rejected_attempts(): void
    {
        [$tenant, $center, $user] = $this->createTenantStack('P14');
        Permission::findOrCreate(Permissions::EMPLOYEE_PROFILE_VIEW, 'web');
        app(PermissionRegistrar::class)->setPermissionsTeamId($tenant->id);
        $employeeRole = Role::firstOrCreate([
            'name' => 'employee',
            'guard_name' => 'web',
            'tenant_id' => $tenant->id,
        ]);
        $user->assignRole($employeeRole);
        $user->givePermissionTo(Permissions::EMPLOYEE_PROFILE_VIEW);

        $employee = Employee::create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'user_id' => $user->id,
            'name_ar' => 'موظف نطاق جغرافي',
            'name_en' => 'Geofence Employee',
            'status' => 'active',
        ]);

        AttendanceSettings::getForCenter($center->id)->update([
            'geofence_enabled' => true,
            'geofence_latitude' => 24.7136000,
            'geofence_longitude' => 46.6753000,
            'geofence_radius_meters' => 200,
        ]);

        // 1. Rejected attempt outside geofence -> no row in hr_attendances, but logged in hr_attendance_geofence_logs
        $rejectedResponse = $this->actingAs($user)->post(route('employee.attendance.clock-in'), [
            'latitude' => 24.7250000,
            'longitude' => 46.6900000,
            'accuracy' => 25.5,
        ]);
        $rejectedResponse->assertSessionHasErrors('location');
        $this->assertDatabaseMissing('hr_attendances', ['employee_id' => $employee->id]);
        $this->assertDatabaseHas('hr_attendance_geofence_logs', [
            'employee_id' => $employee->id,
            'action' => 'clock_in',
            'status' => 'rejected',
            'rejection_reason' => 'outside_geofence',
            'geofence_verified' => false,
        ]);

        // 2. Accepted attempt inside geofence with low GPS accuracy (> 100m) -> stored with low_confidence = true
        $acceptedResponse = $this->actingAs($user)->post(route('employee.attendance.clock-in'), [
            'latitude' => 24.7136500,
            'longitude' => 46.6753500,
            'accuracy' => 145.0,
        ]);
        $acceptedResponse->assertSessionHasNoErrors();

        $attendance = Attendance::withoutGlobalScopes()->where('employee_id', $employee->id)->firstOrFail();
        $this->assertTrue($attendance->geofence_verified);
        $this->assertTrue($attendance->low_confidence);
        $this->assertEqualsWithDelta(145.0, (float) $attendance->accuracy, 0.01);
        $this->assertNotNull($attendance->distance_meters);
    }

    public function test_p2_5_and_p2_6_payroll_accounting_breakdown_employer_gosi_and_idempotent_locking(): void
    {
        [$tenant, $center, $user] = $this->createTenantStack('P25');
        $this->actingAs($user);

        $saudi = Nationality::firstOrCreate(
            ['code' => 'SA'],
            ['name_ar' => 'سعودي', 'name_en' => 'Saudi', 'is_saudi' => true, 'is_active' => true]
        );

        $employee = Employee::create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'nationality_id' => $saudi->id,
            'name_ar' => 'موظف مسير',
            'name_en' => 'Payroll Employee',
            'base_salary' => 10000,
            'gosi_rate' => 9.75,
            'status' => 'active',
        ]);

        $run = PayrollRun::create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'period_start' => '2026-05-01',
            'period_end' => '2026-05-31',
            'payment_date' => '2026-05-31',
            'status' => 'paid',
            'created_by' => $user->id,
            'approved_by' => $user->id,
        ]);

        PayrollItem::create([
            'payroll_run_id' => $run->id,
            'employee_id' => $employee->id,
            'base_salary' => 10000,
            'total_allowances' => 1000,
            'total_deductions' => 1275,
            'gosi_rate' => 9.75,
            'gosi_amount' => 975,
            'net_salary' => 9725,
            'created_by' => $user->id,
        ]);

        $service = app(PayrollAccountingService::class);
        $breakdown = $service->calculatePayrollCostBreakdown($run);

        $this->assertSame(975.0, $breakdown['employee_gosi_amount']);
        $this->assertSame(1175.0, $breakdown['employer_gosi_amount']);
        $this->assertSame(2150.0, $breakdown['total_gosi_remittance']);
        $this->assertSame(9725.0, $breakdown['net_salary_disbursement']);
        $this->assertSame(11875.0, $breakdown['total_employer_cost']);

        // Simulate concurrent/stale second call using an unrefreshed PayrollRun instance
        $staleRunCopy = PayrollRun::withoutGlobalScopes()->findOrFail($run->id);
        $tx1 = $service->postPayrollRunExpense($run);
        $tx2 = $service->postPayrollRunExpense($staleRunCopy);

        $this->assertNotNull($tx1);
        $this->assertNotNull($tx2);
        $this->assertSame($tx1->id, $tx2->id);
        $this->assertSame(1, CompanyTransaction::withoutGlobalScopes()->where('id', $tx1->id)->count());
    }

    public function test_p3_7_p3_8_p3_9_p3_10_commission_cap_non_warranty_share_correction_fillable_and_tenancy_context(): void
    {
        [$tenant, $center, $user] = $this->createTenantStack('P37');
        $this->actingAs($user);

        Permission::findOrCreate(Permissions::HR_EMPLOYEES_UPDATE, 'web');
        app(PermissionRegistrar::class)->setPermissionsTeamId($tenant->id);
        $user->givePermissionTo(Permissions::HR_EMPLOYEES_UPDATE);

        $jobTitle = JobTitle::create([
            'tenant_id' => $tenant->id,
            'name_ar' => 'فني',
            'name_en' => 'Technician',
            'is_active' => true,
        ]);

        $employee = Employee::create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'user_id' => $user->id,
            'job_title_id' => $jobTitle->id,
            'name_ar' => 'فني عمولة',
            'name_en' => 'Commission Tech',
            'email' => 'tech-p38@example.com',
            'status' => 'active',
        ]);

        // P3-8: Reject percentage commission_rate > 100
        $invalidCommission = $this->actingAs($user)->put(route('app.hr.employees.update', $employee), [
            'name_ar' => $employee->name_ar,
            'name_en' => $employee->name_en,
            'email' => $employee->email,
            'status' => 'active',
            'commission_enabled' => true,
            'commission_type' => 'percentage',
            'commission_rate' => 500,
        ]);
        $invalidCommission->assertSessionHasErrors('commission_rate');

        // P3-7: TechnicianCommissionService exposes non_warranty_share only (no first_time_fix_rate)
        $metrics = app(TechnicianCommissionService::class)->calculateForPeriod($employee, now()->startOfMonth(), now()->endOfMonth());
        $this->assertArrayHasKey('non_warranty_share', $metrics);
        $this->assertArrayNotHasKey('first_time_fix_rate', $metrics);
        $this->assertSame(100.0, $metrics['non_warranty_share']);

        // P3-9: AttendanceCorrectionRequest blocks mass assignment of status/reviewed_by on existing records
        $correction = AttendanceCorrectionRequest::create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'employee_id' => $employee->id,
            'date' => '2026-04-12',
            'requested_check_in' => '08:00',
            'reason' => 'نسيت البصمة',
            'status' => AttendanceCorrectionRequest::STATUS_PENDING,
        ]);

        $correction->fill([
            'reason' => 'تحديث السبب',
            'status' => AttendanceCorrectionRequest::STATUS_APPROVED,
            'reviewed_by' => $user->id,
            'reviewed_at' => now(),
        ])->save();

        $correction->refresh();
        $this->assertSame('تحديث السبب', $correction->reason);
        $this->assertSame(AttendanceCorrectionRequest::STATUS_PENDING, $correction->status);
        $this->assertNull($correction->reviewed_by);

        // P3-10: Dead method TenancyContext::isSystemOrPublicContext is removed
        $this->assertFalse(method_exists(TenancyContext::class, 'isSystemOrPublicContext'));
    }

    public function test_transferred_employee_payroll_includes_previous_branch_attendance_and_audit_note(): void
    {
        [$tenant, $centerA, $user] = $this->createTenantStack('TR');
        $centerB = Center::create([
            'tenant_id' => $tenant->id,
            'name' => 'Center B Transfer',
            'slug' => 'center-b-tr-'.uniqid(),
            'is_active' => true,
        ]);

        $user->centers()->syncWithoutDetaching([
            $centerA->id => ['tenant_id' => $tenant->id],
            $centerB->id => ['tenant_id' => $tenant->id],
        ]);
        $user->forceFill(['current_center_id' => $centerB->id])->save();

        Permission::findOrCreate(Permissions::HR_PAYROLL_VIEW, 'web');
        Permission::findOrCreate(Permissions::HR_PAYROLL_CREATE, 'web');
        app(PermissionRegistrar::class)->setPermissionsTeamId($tenant->id);
        $user->givePermissionTo([Permissions::HR_PAYROLL_VIEW, Permissions::HR_PAYROLL_CREATE]);

        AttendanceSettings::getForCenter($centerB->id)->update([
            'late_deduction_per_minute' => 2.0,
            'absence_deduction_type' => 'fixed',
            'absence_deduction_value' => 0,
            'overtime_enabled' => true,
            'overtime_rate_per_hour' => 50.0,
            'working_days' => [],
        ]);

        $employee = Employee::create([
            'tenant_id' => $tenant->id,
            'center_id' => $centerB->id,
            'name_ar' => 'موظف منقول بين الفروع',
            'name_en' => 'Transferred Employee',
            'base_salary' => 6000,
            'gosi_rate' => 0,
            'status' => 'active',
        ]);

        // Attendance in previous branch (Center A): 30m late (60 SAR deduction) + 120m overtime (2h = 100 SAR)
        Attendance::withoutGlobalScopes()->create([
            'tenant_id' => $tenant->id,
            'center_id' => $centerA->id,
            'employee_id' => $employee->id,
            'date' => '2026-06-05',
            'status' => 'present',
            'check_in' => '08:30',
            'check_out' => '18:00',
            'late_minutes' => 30,
            'overtime_minutes' => 120,
        ]);

        // Attendance in current branch (Center B): 15m late (30 SAR deduction) + 60m overtime (1h = 50 SAR)
        Attendance::withoutGlobalScopes()->create([
            'tenant_id' => $tenant->id,
            'center_id' => $centerB->id,
            'employee_id' => $employee->id,
            'date' => '2026-06-15',
            'status' => 'present',
            'check_in' => '08:15',
            'check_out' => '17:00',
            'late_minutes' => 15,
            'overtime_minutes' => 60,
        ]);

        $response = $this->actingAs($user)->post(route('app.hr.payroll.generate'), [
            'period_start' => '2026-06-01',
            'period_end' => '2026-06-30',
        ]);
        $response->assertRedirect();
        $response->assertSessionHasNoErrors();

        $run = PayrollRun::withoutGlobalScopes()->where('tenant_id', $tenant->id)->latest('id')->firstOrFail();
        $item = PayrollItem::withoutGlobalScopes()
            ->where('payroll_run_id', $run->id)
            ->where('employee_id', $employee->id)
            ->firstOrFail();

        // Total overtime from BOTH Center A (2h) + Center B (1h) = 3h * 50 = 150 SAR
        $this->assertSame(150.0, (float) ($item->allowances_breakdown['overtime'] ?? 0));
        // Total late deduction from BOTH Center A (30m) + Center B (15m) = 45m * 2 = 90 SAR
        $this->assertSame(90.0, (float) ($item->deductions_breakdown['attendance'] ?? 0));
        $this->assertSame([$centerA->id], $item->deductions_breakdown['transferred_from_center_ids'] ?? []);
        $this->assertNotNull($item->notes);
        $this->assertStringContainsString((string) $centerA->id, (string) $item->notes);
    }
}

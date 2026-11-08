<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Center;
use App\Models\Customer;
use App\Models\Department;
use App\Models\Payment;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleMake;
use App\Models\WorkOrder;
use App\Models\WorkOrderItem;
use App\Support\Permissions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class ReportsBranchScopingTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    private Center $centerNorth;

    private Center $centerSouth;

    private User $multiBranchAdmin;

    private User $singleBranchUser;

    private Customer $customerNorth;

    private Customer $customerSouth;

    private Vehicle $vehicleNorth;

    private Vehicle $vehicleSouth;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->tenant = Tenant::factory()->create(['name' => 'شركة الورشة التجريبية']);

        $this->centerNorth = Center::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'فرع الشمال',
            'slug' => 'branch-north-'.uniqid(),
            'is_active' => true,
        ]);

        $this->centerSouth = Center::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'فرع الجنوب',
            'slug' => 'branch-south-'.uniqid(),
            'is_active' => true,
        ]);

        app(PermissionRegistrar::class)->setPermissionsTeamId($this->tenant->id);
        Role::findOrCreate('super_admin', 'web');
        Permission::findOrCreate(Permissions::PAYMENTS_VIEW, 'web');

        // Multi-branch admin currently in Branch North context
        $this->multiBranchAdmin = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'current_center_id' => $this->centerNorth->id,
            'is_system_admin' => true,
        ]);
        $this->multiBranchAdmin->assignRole('super_admin');
        $this->multiBranchAdmin->centers()->attach([
            $this->centerNorth->id => ['tenant_id' => $this->tenant->id],
            $this->centerSouth->id => ['tenant_id' => $this->tenant->id],
        ]);

        // Single-branch user restricted to Branch South
        $this->singleBranchUser = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'current_center_id' => $this->centerSouth->id,
        ]);
        $this->singleBranchUser->givePermissionTo(Permissions::PAYMENTS_VIEW);
        $this->singleBranchUser->centers()->attach([
            $this->centerSouth->id => ['tenant_id' => $this->tenant->id],
        ]);

        $this->customerNorth = Customer::create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->centerNorth->id,
            'type' => 'individual',
            'name' => 'عميل الشمال',
            'phone' => '+966511111111',
        ]);

        $this->customerSouth = Customer::create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->centerSouth->id,
            'type' => 'individual',
            'name' => 'عميل الجنوب',
            'phone' => '+966522222222',
        ]);

        $this->vehicleNorth = Vehicle::create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->centerNorth->id,
            'customer_id' => $this->customerNorth->id,
            'plate_number' => 'أ ب ج 111',
        ]);

        $this->vehicleSouth = Vehicle::create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->centerSouth->id,
            'customer_id' => $this->customerSouth->id,
            'plate_number' => 'س ع ص 222',
        ]);
    }

    public function test_payments_report_defaults_to_active_center_when_no_filter_passed(): void
    {
        Payment::create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->centerNorth->id,
            'amount' => 1500,
            'payment_date' => now(),
            'payment_method' => 'mada',
            'type' => 'payment',
            'reference' => 'PAY-NORTH-001',
        ]);

        Payment::create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->centerSouth->id,
            'amount' => 2500,
            'payment_date' => now(),
            'payment_method' => 'cash',
            'type' => 'payment',
            'reference' => 'PAY-SOUTH-001',
        ]);

        // multiBranchAdmin has current_center_id = centerNorth
        $response = $this->actingAs($this->multiBranchAdmin)->get('/app/reports/payments?range=all');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Reports/Payments')
            ->where('filters.center_id', (string) $this->centerNorth->id)
            ->where('companyInfo.center_name', 'فرع الشمال')
            ->where('kpis.total_collected', 1500)
            ->has('payments.data', 1)
            ->where('payments.data.0.reference', 'PAY-NORTH-001')
        );
    }

    public function test_payments_report_allows_switching_to_all_centers_for_admin(): void
    {
        Payment::create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->centerNorth->id,
            'amount' => 1500,
            'payment_date' => now(),
            'payment_method' => 'mada',
            'type' => 'payment',
            'reference' => 'PAY-NORTH-001',
        ]);

        Payment::create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->centerSouth->id,
            'amount' => 2500,
            'payment_date' => now(),
            'payment_method' => 'cash',
            'type' => 'payment',
            'reference' => 'PAY-SOUTH-001',
        ]);

        $response = $this->actingAs($this->multiBranchAdmin)->get('/app/reports/payments?range=all&center_id=all');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Reports/Payments')
            ->where('filters.center_id', 'all')
            ->where('kpis.total_collected', 4000)
            ->has('payments.data', 2)
        );
    }

    public function test_single_branch_user_cannot_view_all_centers_or_other_center(): void
    {
        Payment::create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->centerNorth->id,
            'amount' => 1500,
            'payment_date' => now(),
            'payment_method' => 'mada',
            'type' => 'payment',
            'reference' => 'PAY-NORTH-001',
        ]);

        Payment::create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->centerSouth->id,
            'amount' => 2500,
            'payment_date' => now(),
            'payment_method' => 'cash',
            'type' => 'payment',
            'reference' => 'PAY-SOUTH-001',
        ]);

        // Attempting center_id=all as a single branch user
        $responseAll = $this->actingAs($this->singleBranchUser)->get('/app/reports/payments?range=all&center_id=all');
        $responseAll->assertStatus(200);
        $responseAll->assertInertia(fn ($page) => $page
            ->where('kpis.total_collected', 2500)
            ->has('payments.data', 1)
            ->where('payments.data.0.reference', 'PAY-SOUTH-001')
        );

        // Attempting to query centerNorth
        $responseNorth = $this->actingAs($this->singleBranchUser)->get('/app/reports/payments?range=all&center_id='.$this->centerNorth->id);
        $responseNorth->assertStatus(200);
        $responseNorth->assertInertia(fn ($page) => $page
            ->where('kpis.total_collected', 0)
            ->has('payments.data', 0)
        );
    }

    public function test_tickets_report_defaults_to_active_center(): void
    {
        WorkOrder::withoutGlobalScope('center_scoped')->create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->centerNorth->id,
            'customer_id' => $this->customerNorth->id,
            'vehicle_id' => $this->vehicleNorth->id,
            'code' => 'WO-NORTH-1',
            'status' => 'open',
            'entry_date' => now()->toDateString(),
            'total_excl_tax' => 100,
            'total_tax' => 15,
            'total_incl_tax' => 115,
            'total_taxable_amount' => 100,
        ]);

        WorkOrder::withoutGlobalScope('center_scoped')->create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->centerSouth->id,
            'customer_id' => $this->customerSouth->id,
            'vehicle_id' => $this->vehicleSouth->id,
            'code' => 'WO-SOUTH-1',
            'status' => 'open',
            'entry_date' => now()->toDateString(),
            'total_excl_tax' => 200,
            'total_tax' => 30,
            'total_incl_tax' => 230,
            'total_taxable_amount' => 200,
        ]);

        $response = $this->actingAs($this->multiBranchAdmin)->get('/app/reports/tickets?range=all');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Reports/Tickets')
            ->where('filters.center_id', (string) $this->centerNorth->id)
            ->where('companyInfo.center_name', 'فرع الشمال')
            ->where('kpis.total_records', 1)
            ->has('tickets.data', 1)
            ->where('tickets.data.0.code', 'WO-NORTH-1')
        );
    }

    public function test_services_report_defaults_to_active_center(): void
    {
        $deptNorth = Department::withoutGlobalScope('center_scoped')->create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->centerNorth->id,
            'name_ar' => 'ميكانيكا شمال',
            'name_en' => 'Mechanics North',
            'is_active' => true,
        ]);

        $deptSouth = Department::withoutGlobalScope('center_scoped')->create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->centerSouth->id,
            'name_ar' => 'ميكانيكا جنوب',
            'name_en' => 'Mechanics South',
            'is_active' => true,
        ]);

        $woNorth = WorkOrder::withoutGlobalScope('center_scoped')->create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->centerNorth->id,
            'customer_id' => $this->customerNorth->id,
            'vehicle_id' => $this->vehicleNorth->id,
            'code' => 'WO-NORTH-SRV',
            'status' => 'open',
            'entry_date' => now()->toDateString(),
        ]);

        $woSouth = WorkOrder::withoutGlobalScope('center_scoped')->create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->centerSouth->id,
            'customer_id' => $this->customerSouth->id,
            'vehicle_id' => $this->vehicleSouth->id,
            'code' => 'WO-SOUTH-SRV',
            'status' => 'open',
            'entry_date' => now()->toDateString(),
        ]);

        WorkOrderItem::withoutGlobalScope('center_scoped')->create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->centerNorth->id,
            'work_order_id' => $woNorth->id,
            'department_id' => $deptNorth->id,
            'title' => 'خدمة فرع الشمال',
            'qty' => 1,
            'unit_price' => 300,
            'discount_amount' => 0,
            'line_total' => 300,
            'status' => WorkOrderItem::STATUS_COMPLETED,
            'due_date' => now()->toDateString(),
        ]);

        WorkOrderItem::withoutGlobalScope('center_scoped')->create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->centerSouth->id,
            'work_order_id' => $woSouth->id,
            'department_id' => $deptSouth->id,
            'title' => 'خدمة فرع الجنوب',
            'qty' => 1,
            'unit_price' => 500,
            'discount_amount' => 0,
            'line_total' => 500,
            'status' => WorkOrderItem::STATUS_COMPLETED,
            'due_date' => now()->toDateString(),
        ]);

        $response = $this->actingAs($this->multiBranchAdmin)->get('/app/reports/services?range=all');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Reports/Services')
            ->where('filters.center_id', (string) $this->centerNorth->id)
            ->where('meta.center_name', 'فرع الشمال')
            ->where('kpis.total_records', 1)
            ->where('services.data.0.service_name', 'خدمة فرع الشمال')
        );
    }

    public function test_export_payments_csv_defaults_to_active_center(): void
    {
        Payment::create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->centerNorth->id,
            'amount' => 1500,
            'payment_date' => now(),
            'payment_method' => 'mada',
            'type' => 'payment',
            'reference' => 'PAY-NORTH-EXP',
        ]);

        Payment::create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->centerSouth->id,
            'amount' => 2500,
            'payment_date' => now(),
            'payment_method' => 'cash',
            'type' => 'payment',
            'reference' => 'PAY-SOUTH-EXP',
        ]);

        $response = $this->actingAs($this->multiBranchAdmin)->get('/app/reports/payments/export?range=all');

        $response->assertStatus(200);
        $csv = $response->streamedContent();

        $this->assertStringContainsString('PAY-NORTH-EXP', $csv);
        $this->assertStringNotContainsString('PAY-SOUTH-EXP', $csv);
    }

    public function test_vehicle_make_logo_url_is_resolved_in_reports(): void
    {
        $make = VehicleMake::create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->centerNorth->id,
            'name_ar' => 'مرسيدس بنز',
            'name_en' => 'Mercedes-Benz',
            'logo_path' => 'makes/mercedes.png',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->multiBranchAdmin)->get('/app/reports/payments?range=all');
        $response->assertStatus(200);

        $makes = $response->viewData('page')['props']['makes'];
        $found = collect($makes)->firstWhere('id', $make->id);

        $this->assertNotNull($found);
        $this->assertNotNull($found['logo_url']);
        $this->assertStringContainsString('mercedes.png', $found['logo_url']);
    }
}

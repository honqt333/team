<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Center;
use App\Models\Quote;
use App\Models\Tenant;
use App\Models\User;
use App\Models\WorkOrder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class TenantSettingsParityTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;

    protected Center $center;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::factory()->create([
            'status' => 'active',
            'invoice_number_format' => 'INV-{CENTER}-{YYYY}-{SEQ}',
            'work_order_number_format' => 'WO-{SEQ}',
            'quote_number_format' => 'QT-{SEQ}',
        ]);

        $this->center = Center::factory()->create(['tenant_id' => $this->tenant->id]);
        $this->user = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'current_center_id' => $this->center->id,
        ]);
        $this->user->centers()->attach($this->center->id, ['tenant_id' => $this->tenant->id]);

        Permission::firstOrCreate(['name' => 'settings.company.manage', 'guard_name' => 'web']);
        app(PermissionRegistrar::class)->setPermissionsTeamId($this->tenant->id);
        $this->user->givePermissionTo('settings.company.manage');
    }

    public function test_can_update_numbering_settings(): void
    {
        $this->actingAsWithTeam($this->user);

        $response = $this->put('/app/settings/company', [
            'section' => 'numbering',
            'data' => [
                'invoice_number_format' => 'INV-{YYYY}-{SEQ}',
                'work_order_number_format' => 'JC-{SEQ}',
                'quote_number_format' => 'EST-{SEQ}',
            ],
        ]);

        $response->assertSessionHasNoErrors();

        $this->tenant->refresh();
        $this->assertEquals('INV-{YYYY}-{SEQ}', $this->tenant->invoice_number_format);
        $this->assertEquals('JC-{SEQ}', $this->tenant->work_order_number_format);
        $this->assertEquals('EST-{SEQ}', $this->tenant->quote_number_format);
    }

    public function test_work_order_code_generation_respects_custom_format(): void
    {
        $this->tenant->update([
            'work_order_number_format' => 'JC-{SEQ}',
        ]);

        $code = WorkOrder::generateCode($this->tenant->id, $this->center->id);
        $this->assertStringStartsWith('JC-', $code);
        $this->assertEquals('JC-000001', $code);
    }

    public function test_quote_code_generation_respects_custom_format(): void
    {
        $this->tenant->update([
            'quote_number_format' => 'EST-{SEQ}',
        ]);

        $code = Quote::generateCode($this->tenant->id, $this->center->id);
        $this->assertStringStartsWith('EST-', $code);
        $this->assertEquals('EST-000001', $code);
    }

    public function test_can_update_workflow_policies(): void
    {
        $this->actingAsWithTeam($this->user);

        $response = $this->put('/app/settings/company', [
            'section' => 'workflow',
            'data' => [
                'allow_lower_odometer' => true,
                'max_discount_percentage' => 25,
                'require_phone_on_creation' => true,
                'default_warranty_days' => 60,
            ],
        ]);

        $response->assertSessionHasNoErrors();

        $this->tenant->refresh();
        $this->assertTrue($this->tenant->getWorkflowSetting('allow_lower_odometer'));
        $this->assertEquals(25, $this->tenant->getWorkflowSetting('max_discount_percentage'));
        $this->assertTrue($this->tenant->getWorkflowSetting('require_phone_on_creation'));
        $this->assertEquals(60, $this->tenant->getWorkflowSetting('default_warranty_days'));
    }

    public function test_can_update_notification_templates(): void
    {
        $this->actingAsWithTeam($this->user);

        $response = $this->put('/app/settings/company', [
            'section' => 'notifications',
            'data' => [
                'work_order_created' => 'تم استلام سيارتك {plate_number} برقم #{code}',
                'work_order_completed' => 'سيارتك {plate_number} جاهزة في {center_name}',
                'quote_shared' => 'عرض السعر #{code}: {url}',
                'invoice_issued' => 'فاتورتك #{code} بمبلغ {total} ر.س',
            ],
        ]);

        $response->assertSessionHasNoErrors();

        $this->tenant->refresh();
        $this->assertEquals(
            'تم استلام سيارتك {plate_number} برقم #{code}',
            $this->tenant->getNotificationTemplate('work_order_created')
        );
        $this->assertEquals(
            'سيارتك {plate_number} جاهزة في {center_name}',
            $this->tenant->getNotificationTemplate('work_order_completed')
        );
    }
}

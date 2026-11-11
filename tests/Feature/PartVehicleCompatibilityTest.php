<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Center;
use App\Models\InventoryUnit;
use App\Models\Part;
use App\Models\Tenant;
use App\Models\User;
use App\Models\VehicleMake;
use App\Models\VehicleModel;
use App\Support\Permissions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class PartVehicleCompatibilityTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;

    protected Center $center;

    protected User $user;

    protected InventoryUnit $unit;

    protected VehicleMake $toyota;

    protected VehicleModel $camry;

    protected VehicleMake $ford;

    protected function setUp(): void
    {
        parent::setUp();

        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        foreach ([
            Permissions::INVENTORY_VIEW,
            Permissions::INVENTORY_MOVES_CREATE,
        ] as $perm) {
            Permission::findOrCreate($perm, 'web');
        }

        $this->tenant = Tenant::create([
            'name' => 'Parts Compat Workshop',
            'slug' => 'parts-compat-workshop',
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
            Permissions::INVENTORY_VIEW,
            Permissions::INVENTORY_MOVES_CREATE,
        ]);

        $this->unit = InventoryUnit::create([
            'tenant_id' => $this->tenant->id,
            'name_ar' => 'قطعة',
            'name_en' => 'Piece',
            'is_active' => true,
        ]);

        $this->toyota = VehicleMake::create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
            'name_ar' => 'تويوتا',
            'name_en' => 'Toyota',
            'is_active' => true,
        ]);

        $this->camry = VehicleModel::create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
            'make_id' => $this->toyota->id,
            'name_ar' => 'كامري',
            'name_en' => 'Camry',
            'is_active' => true,
        ]);

        $this->ford = VehicleMake::create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
            'name_ar' => 'فورد',
            'name_en' => 'Ford',
            'is_active' => true,
        ]);
    }

    public function test_part_can_be_created_updated_and_filtered_by_vehicle_compatibility(): void
    {
        $storeResponse = $this->actingAs($this->user)
            ->post(route('app.inventory.parts.store'), [
                'sku' => 'OIL-FILTER-CAMRY',
                'name_ar' => 'فلتر زيت كامري',
                'name_en' => 'Camry Oil Filter',
                'unit_id' => $this->unit->id,
                'compatibilities' => [
                    [
                        'vehicle_make_id' => $this->toyota->id,
                        'vehicle_model_id' => $this->camry->id,
                        'year_from' => 2018,
                        'year_to' => 2024,
                    ],
                ],
            ]);

        $storeResponse->assertRedirect(route('app.inventory.parts.index'));

        $part = Part::where('sku', 'OIL-FILTER-CAMRY')->firstOrFail();

        $this->assertDatabaseHas('part_vehicle_compatibilities', [
            'tenant_id' => $this->tenant->id,
            'part_id' => $part->id,
            'vehicle_make_id' => $this->toyota->id,
            'vehicle_model_id' => $this->camry->id,
            'year_from' => 2018,
            'year_to' => 2024,
        ]);

        // Matching filter returns the part
        $matchRes = $this->actingAs($this->user)
            ->get(route('app.inventory.parts.index', [
                'vehicle_make_id' => $this->toyota->id,
                'vehicle_model_id' => $this->camry->id,
                'manufacture_year' => 2020,
            ]));
        $matchRes->assertOk();
        $matchRes->assertInertia(fn ($page) => $page->has('parts.data', 1));

        // Non-matching year excludes the part
        $yearMismatchRes = $this->actingAs($this->user)
            ->get(route('app.inventory.parts.index', [
                'vehicle_make_id' => $this->toyota->id,
                'manufacture_year' => 2010,
            ]));
        $yearMismatchRes->assertOk();
        $yearMismatchRes->assertInertia(fn ($page) => $page->has('parts.data', 0));

        // Update part compatibilities to Ford only
        $updateRes = $this->actingAs($this->user)
            ->put(route('app.inventory.parts.update', $part->id), [
                'sku' => 'OIL-FILTER-CAMRY',
                'name_ar' => 'فلتر زيت فورد',
                'name_en' => 'Ford Oil Filter',
                'unit_id' => $this->unit->id,
                'sync_compatibilities' => true,
                'compatibilities' => [
                    [
                        'vehicle_make_id' => $this->ford->id,
                        'vehicle_model_id' => null,
                        'year_from' => 2021,
                        'year_to' => 2026,
                    ],
                ],
            ]);
        $updateRes->assertRedirect(route('app.inventory.parts.index'));

        $this->assertDatabaseMissing('part_vehicle_compatibilities', [
            'part_id' => $part->id,
            'vehicle_make_id' => $this->toyota->id,
        ]);
        $this->assertDatabaseHas('part_vehicle_compatibilities', [
            'part_id' => $part->id,
            'vehicle_make_id' => $this->ford->id,
        ]);
    }
}

<?php

declare(strict_types=1);

namespace Tests\Feature\Settings;

use App\Models\Center;
use App\Models\Tenant;
use App\Models\User;
use App\Models\VehicleMake;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class SystemSettingsIndexCrudTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;

    protected Tenant $otherTenant;

    protected Center $center;

    protected Center $otherCenter;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::factory()->create(['status' => 'active']);
        $this->otherTenant = Tenant::factory()->create(['status' => 'active']);

        $this->center = Center::factory()->create([
            'tenant_id' => $this->tenant->id,
            'is_main' => true,
            'is_active' => true,
        ]);

        $this->otherCenter = Center::factory()->create([
            'tenant_id' => $this->otherTenant->id,
            'is_main' => true,
            'is_active' => true,
        ]);

        $this->user = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'current_center_id' => $this->center->id,
        ]);
        $this->user->centers()->attach($this->center->id, ['tenant_id' => $this->tenant->id]);
    }

    public function test_user_can_view_makes_models_colors_and_condition_items_sections(): void
    {
        $this->actingAs($this->user);

        $this->get(route('settings.makes.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Settings/System/Index')
                ->where('activeSection', 'makes')
                ->has('makes')
            );

        $this->get(route('settings.models.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Settings/System/Index')
                ->where('activeSection', 'models')
                ->has('models')
            );

        $this->get(route('settings.colors.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Settings/System/Index')
                ->where('activeSection', 'colors')
                ->has('colors')
            );

        $this->get(route('app.condition-items.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Settings/System/Index')
                ->where('activeSection', 'condition-items')
                ->has('condition_items')
            );

        $this->get(route('settings.storage'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Settings/System/Index')
                ->where('activeSection', 'storage')
                ->has('storage_usage')
            );
    }

    public function test_user_can_create_toggle_and_delete_center_make(): void
    {
        $this->actingAs($this->user);

        $this->post(route('settings.makes.store'), [
            'name_ar' => 'تويوتا تجريبي',
            'name_en' => 'Toyota Test',
            'is_active' => true,
        ])->assertRedirect();

        $make = VehicleMake::where('name_en', 'Toyota Test')->firstOrFail();
        $this->assertSame('center', $make->source);
        $this->assertTrue($make->is_active);

        $this->patch(route('settings.makes.toggle', $make))->assertRedirect();
        $this->assertFalse($make->fresh()->is_active);

        $this->delete(route('settings.makes.destroy', $make))->assertRedirect();
        $this->assertSoftDeleted('vehicle_makes', ['id' => $make->id]);
    }

    public function test_system_make_cannot_be_toggled_or_deleted(): void
    {
        $systemMake = VehicleMake::create([
            'source' => 'system',
            'name_ar' => 'نظام أساسي',
            'name_en' => 'System Make',
            'is_active' => true,
        ]);

        $this->actingAs($this->user);

        $this->patch(route('settings.makes.toggle', $systemMake))->assertForbidden();
        $this->delete(route('settings.makes.destroy', $systemMake))->assertForbidden();
    }
}

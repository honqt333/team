<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Center;
use App\Models\Setting;
use App\Models\Tenant;
use App\Models\User;
use App\Support\Permissions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Tests for the systematic-inspection preference (audit finding #49).
 *
 * The preference lived in the system-wide `settings` key/value store,
 * so any authenticated employee of any tenant could flip it and the
 * inspection steps changed for every workshop on the install. The
 * endpoint also asked for no permission at all.
 *
 * The preference is now a tenant-scoped row, and the toggle requires
 * the services-management permission.
 */
class ServiceInspectionSettingScopeTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    private Center $center;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        Permission::findOrCreate(Permissions::SERVICES_VIEW, 'web');
        Permission::findOrCreate(Permissions::SERVICES_UPDATE, 'web');
        Permission::findOrCreate(Permissions::SERVICES_CREATE, 'web');

        $this->tenant = Tenant::factory()->create();
        $this->center = Center::factory()->create(['tenant_id' => $this->tenant->id]);
    }

    private function userWith(array $permissions): User
    {
        $user = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'current_center_id' => $this->center->id,
        ]);

        $user->centers()->attach($this->center->id, ['tenant_id' => $this->tenant->id]);

        app(PermissionRegistrar::class)->setPermissionsTeamId($this->tenant->id);
        $user->givePermissionTo($permissions);
        $user->refresh();

        return $user;
    }

    public function test_a_user_without_the_services_permission_cannot_toggle_the_setting(): void
    {
        $user = $this->userWith([Permissions::SERVICES_VIEW]);

        $response = $this->actingAs($user)
            ->post('/app/services/toggle-inspections-setting');

        $response->assertForbidden();

        $this->assertSame(
            'true',
            Setting::forTenant($this->tenant->id, 'enable_systematic_inspections', 'true')
        );
    }

    public function test_a_user_with_the_services_permission_can_toggle_the_setting(): void
    {
        $user = $this->userWith([Permissions::SERVICES_UPDATE]);

        $response = $this->actingAs($user)
            ->post('/app/services/toggle-inspections-setting');

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();

        $this->assertSame(
            'false',
            Setting::forTenant($this->tenant->id, 'enable_systematic_inspections')
        );
    }

    public function test_the_toggle_does_not_reach_the_other_tenants(): void
    {
        $otherTenant = Tenant::factory()->create();
        $otherCenter = Center::factory()->create(['tenant_id' => $otherTenant->id]);

        $otherUser = User::factory()->create([
            'tenant_id' => $otherTenant->id,
            'current_center_id' => $otherCenter->id,
        ]);
        $otherUser->centers()->attach($otherCenter->id, ['tenant_id' => $otherTenant->id]);
        app(PermissionRegistrar::class)->setPermissionsTeamId($otherTenant->id);
        $otherUser->givePermissionTo(Permissions::SERVICES_UPDATE);
        $otherUser->refresh();

        $user = $this->userWith([Permissions::SERVICES_UPDATE]);

        $this->actingAs($user)->post('/app/services/toggle-inspections-setting');

        // The other tenant still sees the default.
        $this->assertSame(
            'true',
            Setting::forTenant($otherTenant->id, 'enable_systematic_inspections', 'true')
        );

        // And it has no row of its own yet.
        $this->assertDatabaseMissing('settings', [
            'tenant_id' => $otherTenant->id,
            'key' => 'enable_systematic_inspections',
        ]);

        // The system-wide default is untouched too.
        $this->assertSame(
            'true',
            Setting::whereNull('tenant_id')->where('key', 'enable_systematic_inspections')->value('value') ?? 'true'
        );
    }

    public function test_the_global_default_still_applies_to_a_tenant_that_never_toggled_it(): void
    {
        Setting::create([
            'key' => 'enable_systematic_inspections',
            'value' => 'false',
            'group' => 'work_orders',
        ]);

        $this->assertSame(
            'false',
            Setting::forTenant($this->tenant->id, 'enable_systematic_inspections', 'true')
        );
    }

    public function test_a_tenant_that_toggled_it_keeps_its_own_value(): void
    {
        Setting::create([
            'key' => 'enable_systematic_inspections',
            'value' => 'false',
            'group' => 'work_orders',
        ]);

        $user = $this->userWith([Permissions::SERVICES_UPDATE]);

        // The tenant starts from the global default (false) and flips
        // it to true for itself.
        $this->actingAs($user)->post('/app/services/toggle-inspections-setting');

        $this->assertSame(
            'true',
            Setting::forTenant($this->tenant->id, 'enable_systematic_inspections')
        );

        // The global default is still false.
        $this->assertSame(
            'false',
            Setting::whereNull('tenant_id')->where('key', 'enable_systematic_inspections')->value('value')
        );
    }

    public function test_a_tenant_row_never_reaches_the_platform_wide_setting(): void
    {
        // A tenant-specific row and the platform-wide row may carry the
        // same key — the platform-wide read and write must keep to the
        // platform row, or a tenant would overwrite the settings of
        // the whole installation.
        Setting::create([
            'tenant_id' => $this->tenant->id,
            'key' => 'help.contact.phone',
            'value' => '0500000000',
            'group' => 'help',
        ]);

        Setting::create([
            'tenant_id' => null,
            'key' => 'help.contact.phone',
            'value' => '+966555000000',
            'group' => 'help',
        ]);

        $this->assertSame('+966555000000', Setting::get('help.contact.phone'));

        Setting::set('help.contact.phone', '+966111111111', 'help');

        $this->assertSame('+966111111111', Setting::get('help.contact.phone'));

        $this->assertSame(
            '0500000000',
            Setting::where('tenant_id', $this->tenant->id)
                ->where('key', 'help.contact.phone')
                ->value('value'),
            'Writing the platform-wide setting must not touch the row of a tenant.'
        );
    }
}

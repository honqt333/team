<?php

declare(strict_types=1);

namespace Tests\Feature\Settings;

use App\Models\Center;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use App\Support\Permissions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    private Center $center;

    private User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::factory()->create();
        $this->center = Center::factory()->create(['tenant_id' => $this->tenant->id]);

        app(PermissionRegistrar::class)->setPermissionsTeamId($this->tenant->id);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->adminUser = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'current_center_id' => $this->center->id,
            'is_active' => true,
        ]);
        $this->adminUser->centers()->attach($this->center->id, ['tenant_id' => $this->tenant->id]);

        // Grant users permissions
        $this->adminUser->givePermissionTo([
            Permissions::USERS_VIEW,
            Permissions::USERS_CREATE,
            Permissions::USERS_UPDATE,
            Permissions::USERS_DELETE,
        ]);
    }

    public function test_user_can_view_users_index(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('settings.users.index'));

        $response->assertOk();
    }

    public function test_user_can_create_new_user(): void
    {
        $role = Role::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'technician_role',
            'label_ar' => 'فني',
            'label_en' => 'Technician',
            'guard_name' => 'web',
        ]);

        $response = $this->actingAs($this->adminUser)
            ->post(route('settings.users.store'), [
                'name' => 'سامي أحمد',
                'email' => 'sami@example.com',
                'password' => 'secret1234',
                'password_confirmation' => 'secret1234',
                'centers' => [$this->center->id],
                'role_id' => $role->id,
                'is_active' => true,
            ]);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('users', [
            'tenant_id' => $this->tenant->id,
            'email' => 'sami@example.com',
            'name' => 'سامي أحمد',
            'is_active' => true,
        ]);

        $created = User::where('email', 'sami@example.com')->first();
        $this->assertTrue($created->hasRole('technician_role'));
        $this->assertTrue($created->centers->contains($this->center->id));
    }

    public function test_user_can_update_user_without_changing_email(): void
    {
        $targetUser = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'current_center_id' => $this->center->id,
            'name' => 'محمد الأصلي',
            'email' => 'mohammed@example.com',
            'is_active' => true,
        ]);
        $targetUser->centers()->attach($this->center->id, ['tenant_id' => $this->tenant->id]);

        $response = $this->actingAs($this->adminUser)
            ->put(route('settings.users.update', $targetUser->id), [
                'name' => 'محمد المحدث',
                'email' => 'mohammed@example.com', // Same email - should not trigger unique validation error!
                'centers' => [$this->center->id],
                'is_active' => true,
            ]);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('users', [
            'id' => $targetUser->id,
            'name' => 'محمد المحدث',
            'email' => 'mohammed@example.com',
        ]);
    }

    public function test_user_can_update_user_role_and_centers(): void
    {
        $role1 = Role::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'role_one',
            'label_ar' => 'دور 1',
            'label_en' => 'Role 1',
            'guard_name' => 'web',
        ]);
        $role2 = Role::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'role_two',
            'label_ar' => 'دور 2',
            'label_en' => 'Role 2',
            'guard_name' => 'web',
        ]);

        $secondCenter = Center::factory()->create(['tenant_id' => $this->tenant->id]);

        $targetUser = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'current_center_id' => $this->center->id,
            'is_active' => true,
        ]);
        $targetUser->centers()->attach($this->center->id, ['tenant_id' => $this->tenant->id]);
        $targetUser->syncRoles([$role1->id]);

        $this->assertTrue($targetUser->hasRole('role_one'));

        // Change role to role2 and assign second center
        $response = $this->actingAs($this->adminUser)
            ->put(route('settings.users.update', $targetUser->id), [
                'name' => $targetUser->name,
                'email' => $targetUser->email,
                'centers' => [$this->center->id, $secondCenter->id],
                'role_id' => $role2->id,
                'is_active' => true,
            ]);

        $response->assertSessionHasNoErrors();
        $fresh = $targetUser->fresh();
        $this->assertTrue($fresh->hasRole('role_two'));
        $this->assertFalse($fresh->hasRole('role_one'));
        $this->assertCount(2, $fresh->centers);
    }

    public function test_user_can_toggle_user_active_status(): void
    {
        $targetUser = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'current_center_id' => $this->center->id,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->adminUser)
            ->patch(route('settings.users.toggle', $targetUser->id));

        $response->assertSessionHasNoErrors();
        $this->assertFalse($targetUser->fresh()->is_active);

        // Toggle back to active
        $this->actingAs($this->adminUser)
            ->patch(route('settings.users.toggle', $targetUser->id));
        $this->assertTrue($targetUser->fresh()->is_active);
    }

    public function test_user_cannot_deactivate_self(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->patch(route('settings.users.toggle', $this->adminUser->id));

        $response->assertSessionHas('error');
        $this->assertTrue($this->adminUser->fresh()->is_active);
    }
}

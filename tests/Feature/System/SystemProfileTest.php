<?php

declare(strict_types=1);

namespace Tests\Feature\System;

use App\Models\AdminUser;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class SystemProfileTest extends TestCase
{
    use RefreshDatabase;

    private AdminUser $adminUser;

    private User $systemAdminUser;

    private User $regularUser;

    protected function setUp(): void
    {
        parent::setUp();

        $tenant = Tenant::create([
            'name' => 'Profile Test Tenant',
            'trade_name' => 'Profile Co',
            'slug' => 'profile-tenant',
            'email' => 'profile-tenant@example.test',
            'status' => 'active',
        ]);

        $this->adminUser = AdminUser::create([
            'name' => 'System Admin (AdminUser)',
            'email' => 'admin-guard@example.test',
            'phone' => '0500000001',
            'password' => Hash::make('password123'),
            'role' => 'super_admin',
            'is_active' => true,
        ]);

        $this->systemAdminUser = User::factory()->create([
            'tenant_id' => $tenant->id,
            'name' => 'System Admin (User)',
            'email' => 'web-guard@example.test',
            'phone' => '0500000002',
            'password' => Hash::make('password123'),
            'is_system_admin' => true,
        ]);

        $this->regularUser = User::factory()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Regular User',
            'email' => 'regular@example.test',
            'is_system_admin' => false,
        ]);
    }

    public function test_admin_user_can_view_system_profile(): void
    {
        $response = $this->actingAs($this->adminUser, 'admin')
            ->get(route('system.profile.index'));

        $response->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('System/Profile/Index')
                ->where('admin.email', 'admin-guard@example.test')
                ->where('admin.role', 'super_admin')
            );
    }

    public function test_system_admin_tenant_user_can_view_system_profile(): void
    {
        $response = $this->actingAs($this->systemAdminUser, 'web')
            ->get(route('system.profile.index'));

        $response->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('System/Profile/Index')
                ->where('admin.email', 'web-guard@example.test')
                ->where('admin.role', 'super_admin')
            );
    }

    public function test_regular_user_cannot_view_system_profile(): void
    {
        $response = $this->actingAs($this->regularUser, 'web')
            ->get(route('system.profile.index'));

        $response->assertForbidden();
    }

    public function test_unauthenticated_user_cannot_view_system_profile(): void
    {
        $response = $this->get(route('system.profile.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_admin_user_can_update_profile(): void
    {
        $response = $this->actingAs($this->adminUser, 'admin')
            ->put(route('system.profile.update'), [
                'name' => 'Updated Admin User',
                'email' => 'updated-admin@example.test',
                'phone' => '0509999991',
            ]);

        $response->assertRedirect();
        $this->adminUser->refresh();
        $this->assertSame('Updated Admin User', $this->adminUser->name);
        $this->assertSame('updated-admin@example.test', $this->adminUser->email);
        $this->assertSame('0509999991', $this->adminUser->phone);
    }

    public function test_system_admin_tenant_user_can_update_profile(): void
    {
        $response = $this->actingAs($this->systemAdminUser, 'web')
            ->put(route('system.profile.update'), [
                'name' => 'Updated System User',
                'email' => 'updated-user@example.test',
                'phone' => '0509999992',
            ]);

        $response->assertRedirect();
        $this->systemAdminUser->refresh();
        $this->assertSame('Updated System User', $this->systemAdminUser->name);
        $this->assertSame('updated-user@example.test', $this->systemAdminUser->email);
        $this->assertSame('0509999992', $this->systemAdminUser->phone);
    }

    public function test_admin_user_can_update_password(): void
    {
        $response = $this->actingAs($this->adminUser, 'admin')
            ->put(route('system.profile.password'), [
                'current_password' => 'password123',
                'password' => 'NewSecretPassword123!',
                'password_confirmation' => 'NewSecretPassword123!',
            ]);

        $response->assertRedirect();
        $this->adminUser->refresh();
        $this->assertTrue(Hash::check('NewSecretPassword123!', $this->adminUser->password));
    }

    public function test_system_admin_tenant_user_can_update_password(): void
    {
        $response = $this->actingAs($this->systemAdminUser, 'web')
            ->put(route('system.profile.password'), [
                'current_password' => 'password123',
                'password' => 'NewSecretPassword123!',
                'password_confirmation' => 'NewSecretPassword123!',
            ]);

        $response->assertRedirect();
        $this->systemAdminUser->refresh();
        $this->assertTrue(Hash::check('NewSecretPassword123!', $this->systemAdminUser->password));
    }

    public function test_updating_password_fails_with_wrong_current_password(): void
    {
        $response = $this->actingAs($this->systemAdminUser, 'web')
            ->put(route('system.profile.password'), [
                'current_password' => 'wrong-password',
                'password' => 'NewSecretPassword123!',
                'password_confirmation' => 'NewSecretPassword123!',
            ]);

        $response->assertSessionHasErrors(['current_password']);
    }
}

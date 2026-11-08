<?php

declare(strict_types=1);

namespace Tests\Feature\System;

use App\Models\AdminUser;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SystemAdminUsersAndImpersonationTest extends TestCase
{
    use RefreshDatabase;

    private AdminUser $adminUser;

    private User $systemUser;

    private Tenant $targetTenant;

    private User $tenantOwner;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminUser = AdminUser::create([
            'name' => 'Root Super Admin',
            'email' => 'root-admin@example.test',
            'password' => Hash::make('password123'),
            'role' => 'super_admin',
            'is_active' => true,
        ]);

        $this->systemUser = User::factory()->create([
            'name' => 'System Owner User',
            'email' => 'system-user@example.test',
            'password' => Hash::make('password123'),
            'is_system_admin' => true,
        ]);

        $this->targetTenant = Tenant::create([
            'name' => 'Target Tenant',
            'trade_name' => 'Target Workshop',
            'slug' => 'target-workshop',
            'email' => 'target@example.test',
            'status' => 'active',
        ]);

        $this->tenantOwner = User::factory()->create([
            'tenant_id' => $this->targetTenant->id,
            'name' => 'Target Owner',
            'email' => 'owner@target.test',
            'password' => Hash::make('password123'),
            'is_system_admin' => false,
        ]);
    }

    public function test_admin_user_can_create_new_admin_user(): void
    {
        $response = $this->actingAs($this->adminUser, 'admin')
            ->post(route('system.admin-users.store'), [
                'name' => 'New Support Admin',
                'email' => 'new-support@example.test',
                'password' => 'Password123!',
                'role' => 'support',
                'is_active' => true,
                'permissions' => ['system.tenants.view'],
            ]);

        $response->assertRedirect(route('system.admin-users.index'));
        $this->assertDatabaseHas('admin_users', [
            'email' => 'new-support@example.test',
            'role' => 'support',
        ]);
    }

    public function test_system_admin_user_can_create_new_admin_user(): void
    {
        $response = $this->actingAs($this->systemUser, 'web')
            ->post(route('system.admin-users.store'), [
                'name' => 'New Admin Created By Web System Admin',
                'email' => 'created-by-web@example.test',
                'password' => 'Password123!',
                'role' => 'admin',
                'is_active' => true,
                'permissions' => ['system.tenants.view'],
            ]);

        $response->assertRedirect(route('system.admin-users.index'));
        $this->assertDatabaseHas('admin_users', [
            'email' => 'created-by-web@example.test',
            'role' => 'admin',
        ]);
    }

    public function test_admin_user_can_impersonate_tenant_and_stop(): void
    {
        $startResponse = $this->actingAs($this->adminUser, 'admin')
            ->post(route('system.impersonate.start', $this->targetTenant));

        $startResponse->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($this->tenantOwner, 'web');

        $stopResponse = $this->post(route('impersonate.stop'));
        $stopResponse->assertRedirect('/system');
        $this->assertAuthenticatedAs($this->adminUser, 'admin');
    }

    public function test_system_admin_user_can_impersonate_tenant_and_stop(): void
    {
        $startResponse = $this->actingAs($this->systemUser, 'web')
            ->post(route('system.impersonate.start', $this->targetTenant));

        $startResponse->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($this->tenantOwner, 'web');

        $stopResponse = $this->post(route('impersonate.stop'));
        $stopResponse->assertRedirect('/system');
        $this->assertAuthenticatedAs($this->systemUser, 'web');
    }
}

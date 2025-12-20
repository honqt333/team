<?php

namespace Tests\Feature;

use App\Models\Center;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenancyIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_blocks_requests_if_user_has_no_current_center_id(): void
    {
        $tenant = Tenant::factory()->create();
        $center = Center::factory()->create(['tenant_id' => $tenant->id]);

        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'current_center_id' => null, // No center selected
        ]);

        // Assign user to center via pivot
        $user->centers()->attach($center->id, ['tenant_id' => $tenant->id]);

        $response = $this->actingAs($user)->get('/__test/tenancy-ping');

        $response->assertStatus(403);
    }

    public function test_blocks_requests_if_center_is_inactive(): void
    {
        $tenant = Tenant::factory()->create();
        $center = Center::factory()->inactive()->create(['tenant_id' => $tenant->id]);

        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'current_center_id' => $center->id,
        ]);

        // Assign user to center via pivot
        $user->centers()->attach($center->id, ['tenant_id' => $tenant->id]);

        $response = $this->actingAs($user)->get('/__test/tenancy-ping');

        $response->assertStatus(403);
    }

    public function test_blocks_requests_if_user_is_not_assigned_to_center_in_pivot(): void
    {
        $tenant = Tenant::factory()->create();
        $center = Center::factory()->create(['tenant_id' => $tenant->id]);

        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'current_center_id' => $center->id,
        ]);

        // DO NOT assign user to center via pivot

        $response = $this->actingAs($user)->get('/__test/tenancy-ping');

        $response->assertStatus(403);
    }

    public function test_allows_request_if_user_is_assigned_and_center_active(): void
    {
        $tenant = Tenant::factory()->create();
        $center = Center::factory()->create(['tenant_id' => $tenant->id]);

        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'current_center_id' => $center->id,
        ]);

        // Assign user to center via pivot
        $user->centers()->attach($center->id, ['tenant_id' => $tenant->id]);

        $response = $this->actingAs($user)->get('/__test/tenancy-ping');

        $response->assertStatus(200);
        $response->assertSeeText('ok');
    }

    public function test_blocks_cross_tenant_access(): void
    {
        // Create Tenant A with Center A and User A
        $tenantA = Tenant::factory()->create();
        $centerA = Center::factory()->create(['tenant_id' => $tenantA->id]);
        $userA = User::factory()->create([
            'tenant_id' => $tenantA->id,
            'current_center_id' => $centerA->id,
        ]);
        $userA->centers()->attach($centerA->id, ['tenant_id' => $tenantA->id]);

        // Create Tenant B with Center B
        $tenantB = Tenant::factory()->create();
        $centerB = Center::factory()->create(['tenant_id' => $tenantB->id]);

        // Attempt to make User A access Center B (cross-tenant!)
        $userA->update(['current_center_id' => $centerB->id]);

        $response = $this->actingAs($userA)->get('/__test/tenancy-ping');

        $response->assertStatus(403);
    }

    public function test_readonly_mode_allows_get_blocks_post(): void
    {
        $tenant = Tenant::factory()->readonly()->create();
        $center = Center::factory()->create(['tenant_id' => $tenant->id]);

        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'current_center_id' => $center->id,
        ]);

        $user->centers()->attach($center->id, ['tenant_id' => $tenant->id]);

        // GET should succeed
        $getResponse = $this->actingAs($user)->get('/__test/tenancy-ping');
        $getResponse->assertStatus(200);
        $getResponse->assertSeeText('ok');

        // POST should be blocked
        $postResponse = $this->actingAs($user)->post('/__test/tenancy-write');
        $postResponse->assertStatus(403);
    }
}

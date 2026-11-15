<?php

declare(strict_types=1);

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

        // 1. Verify User::fill() mass-assignment protection drops current_center_id on existing record
        $userA->update(['current_center_id' => $centerB->id]);
        $this->assertSame($centerA->id, (int) $userA->fresh()->current_center_id);

        // 2 & 3. Explicitly simulate a cross-tenant pointer using forceFill() in test fixture and verify persistence
        $userA->forceFill(['current_center_id' => $centerB->id])->save();
        $this->assertSame($centerB->id, (int) $userA->fresh()->current_center_id);

        // 4 & 5. Dispatch request through real EnsureCenterContext middleware and assert 403
        $response = $this->actingAs($userA->fresh())->get('/__test/tenancy-ping');
        $response->assertStatus(403);

        // 6. Verify valid center restoration yields authorized 200 response
        $userA->forceFill(['current_center_id' => $centerA->id])->save();
        $this->actingAs($userA->fresh())->get('/__test/tenancy-ping')
            ->assertStatus(200)
            ->assertSeeText('ok');

        // 7. Verify user without valid center pivot assignment is rejected with 403
        $userA->centers()->detach();
        $this->actingAs($userA->fresh())->get('/__test/tenancy-ping')
            ->assertStatus(403);
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

<?php

namespace Tests\Feature\Auth;

use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationEmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_unverified_user_cannot_access_dashboard_after_registration(): void
    {
        // This is the user state immediately after a fresh
        // registration: logged in, but email_verified_at is NULL.
        $tenant = Tenant::factory()->create();
        $center = \App\Models\Center::factory()->create([
            'tenant_id' => $tenant->id,
            'is_active' => true,
            'is_main' => true,
        ]);
        $user = \App\Models\User::factory()->create([
            'tenant_id' => $tenant->id,
            'current_center_id' => $center->id,
            'email_verified_at' => null,   // freshly registered, not verified
            'is_active' => true,
        ]);
        $user->centers()->attach($center->id, ['tenant_id' => $tenant->id]);

        // The dashboard route has the `verified` middleware which
        // should bounce the unverified user to /verify-email
        // instead of letting them in.
        $response = $this->actingAs($user)->get(route('dashboard'));
        $response->assertRedirect(route('verification.notice'));
    }

    public function test_user_with_verified_email_can_access_dashboard(): void
    {
        $tenant = Tenant::factory()->create();
        $center = \App\Models\Center::factory()->create([
            'tenant_id' => $tenant->id,
            'is_active' => true,
            'is_main' => true,
        ]);
        $user = \App\Models\User::factory()->create([
            'tenant_id' => $tenant->id,
            'current_center_id' => $center->id,
            'email_verified_at' => now(),
            'is_active' => true,
        ]);
        $user->centers()->attach($center->id, ['tenant_id' => $tenant->id]);

        $response = $this->actingAs($user)->get(route('dashboard'));
        $response->assertStatus(200);
    }

    public function test_user_with_unverified_email_is_redirected_to_verification_notice(): void
    {
        $tenant = Tenant::factory()->create();
        $center = \App\Models\Center::factory()->create([
            'tenant_id' => $tenant->id,
            'is_active' => true,
            'is_main' => true,
        ]);
        $user = \App\Models\User::factory()->create([
            'tenant_id' => $tenant->id,
            'current_center_id' => $center->id,
            'email_verified_at' => null,
            'is_active' => true,
        ]);
        $user->centers()->attach($center->id, ['tenant_id' => $tenant->id]);

        $response = $this->actingAs($user)->get(route('dashboard'));
        $response->assertRedirect(route('verification.notice'));
    }
}

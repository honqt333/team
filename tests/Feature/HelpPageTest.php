<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\PerformanceBaselineSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HelpPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $response = $this->get('/app/help');
        $response->assertRedirect('/login');
    }

    public function test_authenticated_user_can_view_help_page(): void
    {
        app(PerformanceBaselineSeeder::class)->run();

        $tenant = Tenant::first();
        $user = User::where('tenant_id', $tenant->id)->where('is_system_admin', true)->first();

        $response = $this->actingAs($user)->get('/app/help');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Help/Index')
            ->has('contactSupport')
        );
    }
}

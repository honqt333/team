<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Center;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\PermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class NavigationAndHistoryProtectionTest extends TestCase
{
    use RefreshDatabase;

    protected function createUserWithTwoBranches(): array
    {
        $this->seed(PermissionsSeeder::class);

        $tenant = Tenant::factory()->create(['status' => 'active']);

        // Center 1: only Arabic name provided
        $center1 = Center::factory()->create([
            'tenant_id' => $tenant->id,
            'name' => 'فرع الشمال',
            'name_ar' => 'فرع الشمال',
            'name_en' => null,
            'is_main' => true,
        ]);

        // Center 2: only English name provided
        $center2 = Center::factory()->create([
            'tenant_id' => $tenant->id,
            'name' => 'South Branch',
            'name_ar' => null,
            'name_en' => 'South Branch',
            'is_main' => false,
        ]);

        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'current_center_id' => $center1->id,
            'two_factor_confirmed_at' => now(),
        ]);
        $user->centers()->attach($center1->id, ['tenant_id' => $tenant->id]);
        $user->centers()->attach($center2->id, ['tenant_id' => $tenant->id]);

        app(PermissionRegistrar::class)->setPermissionsTeamId($tenant->id);
        $superAdminRole = Role::firstOrCreate([
            'tenant_id' => $tenant->id,
            'name' => 'super_admin',
            'guard_name' => 'web',
        ]);
        $user->assignRole($superAdminRole);

        return [$tenant, $center1, $center2, $user];
    }

    public function test_branch_switcher_exposes_fallback_names_for_missing_locales_and_switches_cleanly(): void
    {
        [$tenant, $center1, $center2, $user] = $this->createUserWithTwoBranches();

        $response = $this->actingAs($user)->get('/app/customers');
        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->where('center.id', $center1->id)
            ->where('center.name_ar', 'فرع الشمال')
            ->where('center.name_en', 'فرع الشمال')
            ->has('auth.available_centers', 2)
            ->where('auth.available_centers.0.name_en', 'فرع الشمال')
            ->where('auth.available_centers.1.name_ar', 'South Branch')
            ->where('auth.available_centers.1.name_en', 'South Branch')
        );

        // Switch center -> must update current_center_id and clear Inertia browser history
        $switchResponse = $this->actingAs($user)
            ->from('/app/customers')
            ->post(route('profile.switch-center'), [
                'center_id' => $center2->id,
            ]);

        $switchResponse->assertRedirect('/app/customers');
        $this->assertSame($center2->id, $user->fresh()->current_center_id);

        // Next Inertia response after center switch carries clearHistory = true
        $afterSwitch = $this->actingAs($user->fresh())->get('/app/customers');
        $afterSwitch->assertOk();
        $afterSwitch->assertInertia(function ($page) use ($center2) {
            $page->where('center.id', $center2->id)
                ->where('center.name_ar', 'South Branch')
                ->where('center.name_en', 'South Branch');

            $this->assertTrue($page->toArray()['clearHistory']);
        });
    }

    public function test_responses_send_no_cache_headers_and_mutations_clear_inertia_history(): void
    {
        [$tenant, $center1, $center2, $user] = $this->createUserWithTwoBranches();

        $getResponse = $this->actingAs($user)->get('/app/customers');
        $getResponse->assertOk();
        $cacheControl = (string) $getResponse->headers->get('Cache-Control');
        $this->assertStringContainsString('no-store', $cacheControl);
        $this->assertStringContainsString('no-cache', $cacheControl);
        $this->assertStringContainsString('must-revalidate', $cacheControl);
        $this->assertSame('no-cache', $getResponse->headers->get('Pragma'));

        // A mutating POST redirect invalidates prior history snapshots
        $postResponse = $this->actingAs($user)
            ->from('/app/customers')
            ->post('/app/customers', [
                'name' => 'No Back Tamper Customer',
                'phone' => '0551112233',
            ]);
        $postResponse->assertRedirect();

        $nextPage = $this->actingAs($user)->get('/app/customers');
        $nextPage->assertOk();
        $nextPage->assertInertia(function ($page) {
            $this->assertTrue($page->toArray()['clearHistory']);
        });
    }
}

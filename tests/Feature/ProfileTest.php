<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Center;
use App\Models\Setting;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    protected function createTenantUser(): User
    {
        $tenant = Tenant::factory()->create();
        $center = Center::factory()->create(['tenant_id' => $tenant->id]);

        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'current_center_id' => $center->id,
        ]);

        $user->centers()->attach($center->id, ['tenant_id' => $tenant->id]);

        return $user;
    }

    public function test_profile_page_is_displayed(): void
    {
        $user = $this->createTenantUser();

        $response = $this
            ->actingAs($user)
            ->get('/app/profile');

        $response->assertOk();
    }

    public function test_profile_page_shares_required_wrshah_props(): void
    {
        $user = $this->createTenantUser();

        // Add a mock session
        DB::table('sessions')->insert([
            'id' => 'test-session-id',
            'user_id' => $user->id,
            'ip_address' => '192.168.1.100',
            'user_agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7)',
            'payload' => 'payload',
            'last_activity' => time(),
        ]);

        $response = $this
            ->actingAs($user)
            ->get('/app/profile');

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('App/Profile/Index')
            ->has('preferences')
            ->has('signInHistory')
            ->has('roles')
            ->has('permissions')
            ->has('employeeDetails')
            ->where('signInHistory.0.ip_address', '192.168.1.100')
        );
    }

    public function test_user_profile_legacy_routes_redirect(): void
    {
        $user = $this->createTenantUser();

        $response1 = $this->actingAs($user)->get('/userProfile');
        $response1->assertRedirect(route('app.profile'));

        $response2 = $this->actingAs($user)->get('/app/userProfile');
        $response2->assertRedirect(route('app.profile'));
    }

    public function test_user_preferences_can_be_updated(): void
    {
        $user = $this->createTenantUser();

        $response = $this
            ->actingAs($user)
            ->post('/app/profile/preferences', [
                'layout' => 'top',
                'open_whatsapp' => 'web',
                'whatsapp_sharing_preference' => 'direct',
                'hide_floating_button' => true,
                'hide_floating_contact' => false,
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $saved = Setting::forTenant($user->tenant_id, 'user_preferences.'.$user->id);
        $this->assertNotNull($saved);

        $decoded = json_decode((string) $saved, true);
        $this->assertSame('top', $decoded['layout']);
        $this->assertSame('web', $decoded['open_whatsapp']);
        $this->assertSame('direct', $decoded['whatsapp_sharing_preference']);
        $this->assertTrue($decoded['hide_floating_button']);
        $this->assertFalse($decoded['hide_floating_contact']);
    }

    public function test_profile_information_can_be_updated(): void
    {
        $user = $this->createTenantUser();

        $response = $this
            ->actingAs($user)
            ->patch('/app/profile', [
                'name' => 'Test User',
                'email' => 'test@example.com',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect(); // redirects back

        $user->refresh();

        $this->assertSame('Test User', $user->name);
        $this->assertSame('test@example.com', $user->email);
        $this->assertNull($user->email_verified_at);
    }

    public function test_email_verification_status_is_unchanged_when_the_email_address_is_unchanged(): void
    {
        $user = $this->createTenantUser();

        $response = $this
            ->actingAs($user)
            ->patch('/app/profile', [
                'name' => 'Test User',
                'email' => $user->email,
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $this->assertNotNull($user->refresh()->email_verified_at);
    }
}

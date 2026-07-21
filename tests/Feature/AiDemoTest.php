<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Center;
use App\Models\Prompt;
use App\Models\Tenant;
use App\Models\User;
use App\Support\Permissions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class AiDemoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        config([
            'logging.channels.ai' => ['driver' => 'null'],
            'services.openai.key' => '',
            'services.anthropic.key' => '',
        ]);

        Permission::findOrCreate(Permissions::VEHICLES_VIEW, 'web');
    }

    public function test_unauthenticated_request_is_rejected(): void
    {
        Http::fake();

        $response = $this->postJson('/api/v1/ai/demo/describe-vehicle', $this->vehiclePayload());

        $response->assertUnauthorized();
        Http::assertNothingSent();
    }

    public function test_authenticated_request_without_tenant_id_fails_closed_before_usage_tracking(): void
    {
        Http::fake();
        $user = User::factory()->create(['tenant_id' => null, 'current_center_id' => null]);

        $this->actingAs($user, 'sanctum');

        $response = $this->postJson('/api/v1/ai/demo/describe-vehicle', $this->vehiclePayload());

        $response->assertStatus(403)
            ->assertJsonPath('message', 'AI usage tracking requires a tenant_id.');
        Http::assertNothingSent();
    }

    public function test_authenticated_request_uses_mock_provider_when_openai_key_is_missing(): void
    {
        Http::fake();
        $user = $this->createTenantUserWithVehicleViewPermission();

        $this->actingAs($user, 'sanctum');
        app(PermissionRegistrar::class)->setPermissionsTeamId($user->tenant_id);

        $response = $this->postJson('/api/v1/ai/demo/describe-vehicle', $this->vehiclePayload());

        $response->assertOk()
            ->assertJsonPath('provider', 'mock')
            ->assertJsonPath('model', 'gpt-4o-mini')
            ->assertJsonPath('usage.tenant_id', $user->tenant_id)
            ->assertJsonPath('usage.cost_micro_cents', 0);

        $this->assertStringContainsString('mock fallback_for=openai', $response->json('description'));
        Http::assertNothingSent();
    }

    public function test_authenticated_request_with_openai_key_routes_to_openai_provider(): void
    {
        config(['services.openai.key' => 'test-openai-key']);

        Http::fake([
            'api.openai.com/*' => Http::response([
                'choices' => [
                    [
                        'message' => [
                            'content' => 'وصف تجريبي من OpenAI',
                        ],
                    ],
                ],
                'usage' => [
                    'prompt_tokens' => 11,
                    'completion_tokens' => 7,
                ],
            ], 200),
        ]);

        $user = $this->createTenantUserWithVehicleViewPermission();

        $this->actingAs($user, 'sanctum');
        app(PermissionRegistrar::class)->setPermissionsTeamId($user->tenant_id);

        $response = $this->postJson('/api/v1/ai/demo/describe-vehicle', $this->vehiclePayload());

        $response->assertOk()
            ->assertJsonPath('provider', 'openai')
            ->assertJsonPath('description', 'وصف تجريبي من OpenAI')
            ->assertJsonPath('usage.input_tokens', 11)
            ->assertJsonPath('usage.output_tokens', 7)
            ->assertJsonPath('usage.cost_micro_cents', 585);

        Http::assertSent(function ($request): bool {
            return $request->url() === 'https://api.openai.com/v1/chat/completions'
                && $request->hasHeader('Authorization', 'Bearer test-openai-key')
                && $request['model'] === 'gpt-4o-mini';
        });
    }

    public function test_prompt_model_is_tenant_scoped_and_autofills_tenant_id(): void
    {
        $user = $this->createTenantUserWithVehicleViewPermission();
        $otherTenant = Tenant::factory()->create();

        $this->actingAs($user);
        app(PermissionRegistrar::class)->setPermissionsTeamId($user->tenant_id);

        $prompt = Prompt::create([
            'key' => 'vehicle.describe',
            'version' => 1,
            'content' => 'Describe this vehicle.',
            'model' => 'gpt-4o-mini',
            'temperature' => 0.3,
            'active' => true,
        ]);

        Prompt::withoutGlobalScope('tenant_scoped')->create([
            'tenant_id' => $otherTenant->id,
            'key' => 'vehicle.describe',
            'version' => 1,
            'content' => 'Other tenant prompt.',
            'model' => 'gpt-4o-mini',
            'temperature' => 0.3,
            'active' => true,
        ]);

        $this->assertSame($user->tenant_id, $prompt->tenant_id);
        $this->assertSame([$user->tenant_id], Prompt::query()->pluck('tenant_id')->all());
    }

    private function createTenantUserWithVehicleViewPermission(): User
    {
        $tenant = Tenant::factory()->create();
        $center = Center::factory()->create(['tenant_id' => $tenant->id]);
        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'current_center_id' => $center->id,
        ]);

        $user->centers()->attach($center->id, ['tenant_id' => $tenant->id]);

        app(PermissionRegistrar::class)->setPermissionsTeamId($tenant->id);
        $user->givePermissionTo(Permissions::VEHICLES_VIEW);

        return $user->refresh();
    }

    /**
     * @return array<string, mixed>
     */
    private function vehiclePayload(): array
    {
        return [
            'make' => 'Toyota',
            'model' => 'Camry',
            'year' => 2021,
            'plate_number' => 'ABC 1234',
            'color' => 'White',
            'odometer' => 88000,
            'condition' => 'Needs inspection before quote',
        ];
    }
}

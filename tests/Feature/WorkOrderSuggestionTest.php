<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Center;
use App\Models\Customer;
use App\Models\Department;
use App\Models\Part;
use App\Models\Service;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\WorkOrder;
use App\Support\Permissions;
use Database\Seeders\PermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Feature coverage for the WorkOrder AI suggester endpoint.
 *
 * Spec: docs/features/ai-service-suggester/design.md §10 (backend rows).
 *
 * Conventions matched from `tests/Feature/AiDemoTest.php`:
 *   - Forget Spatie's cached permissions in setUp.
 *   - Seed PermissionsSeeder so `crm.work_orders.update` exists.
 *   - Force OPENAI/ANTHROPIC keys to empty so tests rely on the
 *     default mock unless we explicitly fake OpenAI.
 *   - Pin the AI logging channel to `null` so test output stays clean.
 *   - Http::fake() to mock outbound HTTP for the real-provider tests.
 */
class WorkOrderSuggestionTest extends TestCase
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

        // Re-seed all permissions (covers WORK_ORDERS_UPDATE).
        $this->seed(PermissionsSeeder::class);
    }

    // ---------- #1 Unauthenticated → 401 ----------

    public function test_unauthenticated_request_is_rejected(): void
    {
        Http::fake();

        $user = $this->createTenantUserWithWorkOrderUpdatePermission();
        $workOrder = $this->createWorkOrderFor($user);

        $response = $this->postJson(
            "/api/v1/work-orders/{$workOrder->id}/suggestions",
            ['complaint' => 'صوت طقطقة في الفرامل الأمامية عند الكبح'],
        );

        $response->assertUnauthorized();
        Http::assertNothingSent();
    }

    // ---------- #2 No tenant_id → 403 fail-closed ----------

    public function test_user_without_tenant_id_is_fail_closed_by_track_ai_usage_middleware(): void
    {
        Http::fake();

        $user = User::factory()->create(['tenant_id' => null, 'current_center_id' => null]);

        $this->actingAs($user, 'sanctum');

        $response = $this->postJson(
            '/api/v1/work-orders/1/suggestions',
            ['complaint' => 'صوت طقطقة في الفرامل الأمامية'],
        );

        $response->assertStatus(403)
            ->assertJsonPath('message', 'AI usage tracking requires a tenant_id.');

        Http::assertNothingSent();
    }

    // ---------- #3 Tenant isolation on work order ----------

    public function test_user_in_tenant_a_cannot_query_tenant_b_work_order(): void
    {
        Http::fake();

        [$tenantA, $userA] = $this->makeTenantWithUser(
            'tenant-a',
            tenantPermissions: [Permissions::WORK_ORDERS_UPDATE],
        );

        [, , $centerB, $workOrderB] = $this->makeCrossTenantWorkOrder();

        $this->actingAs($userA, 'sanctum');
        app(PermissionRegistrar::class)->setPermissionsTeamId($tenantA->id);

        $response = $this->postJson(
            "/api/v1/work-orders/{$workOrderB->id}/suggestions",
            ['complaint' => 'صوت طقطقة في الفرامل الأمامية'],
        );

        $response->assertNotFound();
        Http::assertNothingSent();
    }

    // ---------- #4 No OPENAI_API_KEY → provider mock ----------

    public function test_response_uses_mock_provider_when_no_openai_key_is_configured(): void
    {
        Http::fake();

        $user = $this->createTenantUserWithWorkOrderUpdatePermission();
        $workOrder = $this->createWorkOrderFor($user);

        $service = $this->createBrakeInspectionService($user, $workOrder->center_id);
        $part = $this->createBrakePadPart($user);

        $this->actingAs($user, 'sanctum');
        app(PermissionRegistrar::class)->setPermissionsTeamId($user->tenant_id);

        $response = $this->postJson(
            "/api/v1/work-orders/{$workOrder->id}/suggestions",
            [
                'complaint' => 'صوت طقطقة في الفرامل الأمامية عند الكبح',
            ],
        );

        $response->assertOk()
            ->assertJsonPath('meta.provider', 'mock')
            ->assertJsonPath('meta.work_order_id', $workOrder->id)
            ->assertJsonPath('meta.tenant_id', $user->tenant_id)
            ->assertJsonPath('meta.center_id', $workOrder->center_id)
            ->assertJsonPath('usage.cost_micro_cents', 0);

        $suggestions = $response->json('suggestions');
        $this->assertIsArray($suggestions);
        $this->assertNotEmpty($suggestions, 'Mock should produce at least one suggestion when catalog has matches.');

        // The local MockSuggester must have produced a result that
        // actually references our seeded catalog item.
        $suggestedIds = array_map(
            fn (array $row) => ((string) ($row['item_type'] ?? '')).':'.((int) ($row['item_id'] ?? 0)),
            $suggestions,
        );

        $this->assertContains(
            'service:'.$service->id,
            $suggestedIds,
            'MockSuggester must produce the seeded brake-inspection service.',
        );

        Http::assertNothingSent();
    }

    // ---------- #5 With OPENAI_API_KEY + Http::fake → provider=openai ----------

    public function test_response_uses_openai_provider_when_api_key_is_configured(): void
    {
        config(['services.openai.key' => 'test-openai-key']);

        Http::fake([
            'api.openai.com/*' => Http::response([
                'choices' => [
                    [
                        'message' => [
                            'content' => json_encode([
                                'suggestions' => [
                                    [
                                        'item_type' => 'service',
                                        'item_id' => 999, // hallucinated, will be dropped
                                        'name' => 'hallucinated',
                                        'reason' => 'test',
                                        'confidence' => 0.9,
                                        'qty' => 1,
                                    ],
                                ],
                            ], JSON_UNESCAPED_UNICODE),
                        ],
                    ],
                ],
                'usage' => [
                    'prompt_tokens' => 120,
                    'completion_tokens' => 40,
                ],
            ], 200),
        ]);

        $user = $this->createTenantUserWithWorkOrderUpdatePermission();
        $workOrder = $this->createWorkOrderFor($user);

        // Seed a real catalog item the provider has not seen yet so
        // OpenAI's response is dropping the hallucinated id (no
        // catalog row to re-hydrate).
        $this->actingAs($user, 'sanctum');
        app(PermissionRegistrar::class)->setPermissionsTeamId($user->tenant_id);

        $response = $this->postJson(
            "/api/v1/work-orders/{$workOrder->id}/suggestions",
            ['complaint' => 'صوت طقطقة في الفرامل الأمامية عند الكبح'],
        );

        $response->assertOk()
            ->assertJsonPath('meta.provider', 'openai')
            ->assertJsonPath('meta.model', 'gpt-4o-mini')
            ->assertJsonPath('usage.cost_micro_cents', 120 * 15 + 40 * 60) // gpt-4o-mini pricing
            ->assertJsonPath('usage.input_tokens', 120)
            ->assertJsonPath('usage.output_tokens', 40);

        // Hallucinated id was dropped by the defense-in-depth pass.
        $this->assertSame([], $response->json('suggestions'));

        Http::assertSent(function ($request): bool {
            return $request->url() === 'https://api.openai.com/v1/chat/completions'
                && $request->hasHeader('Authorization', 'Bearer test-openai-key')
                && $request['model'] === 'gpt-4o-mini';
        });
    }

    // ---------- #6 AI hallucination defense ----------

    public function test_ai_hallucinated_item_id_from_another_tenant_is_dropped(): void
    {
        Http::fake();

        // 1. Build tenant A: a real work order + the only catalog item.
        $tenantA = Tenant::factory()->create();
        $centerA = Center::factory()->create(['tenant_id' => $tenantA->id]);
        $realService = $this->createServiceFor($tenantA, $centerA, [
            'name_ar' => 'فحص نظام الفرامل',
            'base_price' => 150.00,
        ]);

        // 2. Build tenant B: completely different catalog, completely
        //    different user.
        $tenantB = Tenant::factory()->create();
        $centerB = Center::factory()->create(['tenant_id' => $tenantB->id]);
        $fakeService = $this->createServiceFor($tenantB, $centerB, [
            'name_ar' => 'تغيير زيت المحرك',
            'base_price' => 80.00,
        ]);

        $workOrderA = $this->createWorkOrderForTenant($tenantA, $centerA);

        $userA = User::factory()->create([
            'tenant_id' => $tenantA->id,
            'current_center_id' => $centerA->id,
        ]);
        $userA->centers()->attach($centerA->id, ['tenant_id' => $tenantA->id]);
        app(PermissionRegistrar::class)->setPermissionsTeamId($tenantA->id);
        $userA->givePermissionTo(Permissions::WORK_ORDERS_UPDATE);

        // 3. Force the prompt resolution to a known value (the
        //    service-level Prompt observation is global, so we just
        //    stub OpenAI with a hand-crafted hallucination payload).
        config(['services.openai.key' => 'test-key']);
        Http::fake([
            'api.openai.com/*' => Http::response([
                'choices' => [
                    [
                        'message' => [
                            'content' => json_encode([
                                'suggestions' => [
                                    // Real for tenant A
                                    [
                                        'item_type' => 'service',
                                        'item_id' => $realService->id,
                                        'name' => 'فحص نظام الفرامل',
                                        'reason' => 'مطابقة',
                                        'confidence' => 0.95,
                                        'qty' => 1,
                                    ],
                                    // Hallucinated: lives in tenant B
                                    [
                                        'item_type' => 'service',
                                        'item_id' => $fakeService->id,
                                        'name' => 'تغيير زيت المحرك',
                                        'reason' => 'غير موجود في tenant A',
                                        'confidence' => 0.85,
                                        'qty' => 1,
                                    ],
                                    // Hallucinated: total fabrication
                                    [
                                        'item_type' => 'service',
                                        'item_id' => 999_999,
                                        'name' => 'لا يوجد',
                                        'reason' => 'fabricated',
                                        'confidence' => 0.5,
                                        'qty' => 1,
                                    ],
                                ],
                            ], JSON_UNESCAPED_UNICODE),
                        ],
                    ],
                ],
                'usage' => ['prompt_tokens' => 50, 'completion_tokens' => 30],
            ], 200),
        ]);

        $this->actingAs($userA, 'sanctum');
        app(PermissionRegistrar::class)->setPermissionsTeamId($tenantA->id);

        $response = $this->postJson(
            "/api/v1/work-orders/{$workOrderA->id}/suggestions",
            ['complaint' => 'صوت طقطقة في الفرامل الأمامية'],
        );

        $response->assertOk();

        $suggestions = $response->json('suggestions');
        $this->assertCount(1, $suggestions, 'Hallucinated ids must be dropped.');
        $this->assertSame('service', $suggestions[0]['item_type']);
        $this->assertSame($realService->id, $suggestions[0]['item_id']);

        $returned = (int) $response->json('meta.returned');
        $total = (int) $response->json('meta.total_candidates');

        $this->assertLessThan(
            $total,
            $returned,
            'Returned count must reflect dropped hallucinations.',
        );
        $this->assertSame(1, $returned);
    }

    // ---------- #7 Empty catalog → 200 with empty suggestions ----------

    public function test_empty_catalog_returns_200_with_empty_suggestions(): void
    {
        Http::fake();

        $user = $this->createTenantUserWithWorkOrderUpdatePermission();
        $workOrder = $this->createWorkOrderFor($user);

        $this->actingAs($user, 'sanctum');
        app(PermissionRegistrar::class)->setPermissionsTeamId($user->tenant_id);

        $response = $this->postJson(
            "/api/v1/work-orders/{$workOrder->id}/suggestions",
            ['complaint' => 'صوت طقطقة في الفرامل الأمامية'],
        );

        $response->assertOk()
            ->assertJsonPath('meta.total_candidates', 0)
            ->assertJsonPath('meta.returned', 0)
            ->assertJsonPath('meta.provider', 'mock')
            ->assertJsonPath('suggestions', []);
    }

    // ---------- #8 Validation error ----------

    public function test_invalid_complaint_too_short_returns_422(): void
    {
        Http::fake();

        $user = $this->createTenantUserWithWorkOrderUpdatePermission();
        $workOrder = $this->createWorkOrderFor($user);

        $this->actingAs($user, 'sanctum');
        app(PermissionRegistrar::class)->setPermissionsTeamId($user->tenant_id);

        $response = $this->postJson(
            "/api/v1/work-orders/{$workOrder->id}/suggestions",
            ['complaint' => 'x'],
        );

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['complaint']);

        Http::assertNothingSent();
    }

    // ---------- #9 Provider returns non-JSON → 502 ----------

    public function test_non_json_response_from_provider_returns_502(): void
    {
        config(['services.openai.key' => 'test-key']);
        Http::fake([
            'api.openai.com/*' => Http::response([
                'choices' => [
                    [
                        'message' => [
                            'content' => 'I\'m sorry, I cannot help with that.', // non-JSON
                        ],
                    ],
                ],
                'usage' => ['prompt_tokens' => 10, 'completion_tokens' => 5],
            ], 200),
        ]);

        $user = $this->createTenantUserWithWorkOrderUpdatePermission();
        $workOrder = $this->createWorkOrderFor($user);

        $this->actingAs($user, 'sanctum');
        app(PermissionRegistrar::class)->setPermissionsTeamId($user->tenant_id);

        $response = $this->postJson(
            "/api/v1/work-orders/{$workOrder->id}/suggestions",
            ['complaint' => 'صوت طقطقة في الفرامل الأمامية عند الكبح'],
        );

        $response->assertStatus(502)
            ->assertJsonPath('message', 'AI returned an invalid response.');
    }

    // ============================================================
    // Test helpers
    // ============================================================

    /**
     * @return array{Tenant, User, Center, WorkOrder}
     */
    private function makeTenantWithUser(string $slug, array $tenantPermissions = []): array
    {
        $tenant = Tenant::factory()->create(['slug' => $slug]);
        $center = Center::factory()->create(['tenant_id' => $tenant->id]);

        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'current_center_id' => $center->id,
        ]);
        $user->centers()->attach($center->id, ['tenant_id' => $tenant->id]);

        app(PermissionRegistrar::class)->setPermissionsTeamId($tenant->id);
        foreach ($tenantPermissions as $permissionName) {
            $user->givePermissionTo($permissionName);
        }

        $workOrder = $this->createWorkOrderForTenant($tenant, $center);

        return [$tenant, $user->refresh(), $center, $workOrder];
    }

    /**
     * @return array{Tenant, User, Center, WorkOrder}
     */
    private function makeCrossTenantWorkOrder(): array
    {
        $tenantB = Tenant::factory()->create(['slug' => 'tenant-b']);
        $centerB = Center::factory()->create(['tenant_id' => $tenantB->id]);

        $userB = User::factory()->create([
            'tenant_id' => $tenantB->id,
            'current_center_id' => $centerB->id,
        ]);
        $userB->centers()->attach($centerB->id, ['tenant_id' => $tenantB->id]);

        $workOrderB = $this->createWorkOrderForTenant($tenantB, $centerB);

        return [$tenantB, $userB, $centerB, $workOrderB];
    }

    private function createTenantUserWithWorkOrderUpdatePermission(): User
    {
        $tenant = Tenant::factory()->create();
        $center = Center::factory()->create(['tenant_id' => $tenant->id]);
        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'current_center_id' => $center->id,
        ]);
        $user->centers()->attach($center->id, ['tenant_id' => $tenant->id]);

        app(PermissionRegistrar::class)->setPermissionsTeamId($tenant->id);

        // Defensive belt-and-braces — make sure the permission exists
        // (PermissionsSeeder already ran in setUp).
        Permission::findOrCreate(Permissions::WORK_ORDERS_UPDATE, 'web');

        $user->givePermissionTo(Permissions::WORK_ORDERS_UPDATE);

        return $user->refresh();
    }

    private function createWorkOrderFor(User $user): WorkOrder
    {
        return $this->createWorkOrderForTenant(
            Tenant::query()->withoutGlobalScopes()->findOrFail($user->tenant_id),
            Center::query()->withoutGlobalScopes()->findOrFail($user->current_center_id),
        );
    }

    private function createWorkOrderForTenant(Tenant $tenant, Center $center): WorkOrder
    {
        $customer = Customer::create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'type' => 'individual',
            'name' => 'Test Customer',
            'phone' => '0501234567',
        ]);

        $vehicle = Vehicle::create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'customer_id' => $customer->id,
            'plate_number' => 'ABC 1234',
        ]);

        return WorkOrder::create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'code' => 'WO-'.str_pad((string) random_int(1000, 999_999), 6, '0', STR_PAD_LEFT),
            'status' => 'open',
            'tax_enabled_snapshot' => false,
            'pricing_mode_snapshot' => 'exclusive',
            'tax_rate_snapshot' => 15.00,
            'currency_code' => 'SAR',
            'total_excl_tax' => 0,
            'total_tax' => 0,
            'total_incl_tax' => 0,
        ]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function createServiceFor(Tenant $tenant, Center $center, array $overrides = []): Service
    {
        $defaults = [
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'department_id' => null,
            'name_ar' => 'خدمة اختبار',
            'name_en' => 'Test Service',
            'description_ar' => 'وصف',
            'description_en' => 'Description',
            'base_price' => 100.00,
            'min_price' => 50.00,
            'default_discount_type' => 'none',
            'default_discount_value' => 0,
            'allow_price_override' => true,
            'type' => 'internal',
            'is_active' => true,
            'sort_order' => 0,
        ];

        return Service::create(array_merge($defaults, $overrides));
    }

    private function createBrakeInspectionService(User $user, int $centerId): Service
    {
        $department = Department::create([
            'tenant_id' => $user->tenant_id,
            'center_id' => $centerId,
            'name_ar' => 'فرامل',
            'name_en' => 'Brakes',
            'description' => null,
            'sort_order' => 0,
            'is_active' => true,
        ]);

        return $this->createServiceFor(
            Tenant::query()->withoutGlobalScopes()->findOrFail($user->tenant_id),
            Center::query()->withoutGlobalScopes()->findOrFail($centerId),
            [
                'department_id' => $department->id,
                'name_ar' => 'فحص نظام الفرامل الأمامية',
                'name_en' => 'Brake system inspection',
                'description_ar' => 'فحص شامل لنظام الفرامل',
                'description_en' => 'Comprehensive brake inspection',
                'base_price' => 150.00,
            ],
        );
    }

    private function createBrakePadPart(User $user): Part
    {
        return Part::create([
            'tenant_id' => $user->tenant_id,
            'sku' => 'BRK-'.str_pad((string) random_int(1, 9999), 4, '0', STR_PAD_LEFT),
            'name_ar' => 'تيل فرامل أمامي',
            'name_en' => 'Front brake pads',
            'unit' => 'piece',
            'description' => 'تيل فرامل أمامي أصلي',
            'min_qty' => 0,
            'reorder_qty' => 10,
            'default_sale_price' => 180.00,
            'is_active' => true,
        ]);
    }
}

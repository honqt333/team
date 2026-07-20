<?php

namespace Tests\Unit\Services\AI;

use App\Models\Center;
use App\Models\Tenant;
use App\Models\User;
use App\Models\WorkOrder;
use App\Services\AI\WorkOrderSuggestionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkOrderSuggestionServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_service_returns_structured_suggestions(): void
    {
        config(['services.openai.key' => '']);

        $service = app(WorkOrderSuggestionService::class);
        $tenant = Tenant::factory()->create();
        $center = Center::factory()->forTenant($tenant)->create();
        $user = User::factory()->create(['tenant_id' => $tenant->id, 'current_center_id' => $center->id]);
        $wo = WorkOrder::factory()->create(['tenant_id' => $tenant->id, 'center_id' => $center->id]);

        $request = \Illuminate\Http\Request::create('/', 'POST', ['complaint' => 'Brake noise']);
        $request->setUserResolver(fn() => $user);

        $result = $service->suggest($wo, $request, $user, $tenant->id, $center->id);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('suggestions', $result);
        $this->assertArrayHasKey('meta', $result);
    }
}

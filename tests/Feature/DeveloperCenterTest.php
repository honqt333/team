<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Developer\AuditSnapshot;
use App\Models\User;
use App\Services\Developer\AuditOrchestrator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeveloperCenterTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed basic permissions and configurations if needed
    }

    /**
     * Test regular tenant users cannot access the developer console.
     */
    public function test_regular_tenant_user_is_forbidden_from_developer_console(): void
    {
        $user = User::factory()->create([
            'is_system_admin' => false,
        ]);

        $response = $this->actingAs($user)->get(route('system.developer.index'));
        $response->assertStatus(403); // Forbidden
    }

    /**
     * Test system administrators can access the developer console.
     */
    public function test_system_admin_can_access_developer_console(): void
    {
        $admin = User::factory()->create([
            'is_system_admin' => true,
        ]);

        $response = $this->actingAs($admin)->get(route('system.developer.index'));
        $response->assertStatus(200);
    }

    /**
     * Test running the audit orchestrator generates DB records.
     */
    public function test_audit_orchestrator_runs_successfully_and_records_snapshots(): void
    {
        $orchestrator = new AuditOrchestrator;
        $snapshot = $orchestrator->runAudit();

        $this->assertInstanceOf(AuditSnapshot::class, $snapshot);
        $this->assertDatabaseHas('dev_audit_snapshots', [
            'id' => $snapshot->id,
        ]);

        // Verify overall score is computed
        $this->assertGreaterThan(0.0, $snapshot->score_overall);
        $this->assertNotNull($snapshot->violations_count);
    }
}

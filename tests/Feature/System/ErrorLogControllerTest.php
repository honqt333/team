<?php

declare(strict_types=1);

namespace Tests\Feature\System;

use App\Models\AdminUser;
use App\Models\Tenant;
use App\Models\User;
use App\Support\Permissions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class ErrorLogControllerTest extends TestCase
{
    use RefreshDatabase;

    private string $directory;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->directory = storage_path('framework/testing/error-viewer-'.uniqid());
        File::makeDirectory($this->directory, 0700, true);
        config(['logging.channels.daily-detailed.path' => $this->directory.'/detailed.log']);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->directory);
        parent::tearDown();
    }

    private function admin(array $attributes): AdminUser
    {
        return AdminUser::create(array_merge([
            'name' => 'Recovery reviewer', 'email' => uniqid().'@example.test',
            'password' => 'test-password', 'is_active' => true,
        ], $attributes));
    }

    public function test_tenant_user_is_denied(): void
    {
        $user = User::factory()->create(['tenant_id' => Tenant::factory()->create()->id]);
        $this->actingAs($user)->get('/system/error-log')->assertForbidden();
    }

    public function test_system_account_without_developer_permission_is_denied(): void
    {
        $admin = $this->admin(['role' => 'support', 'permissions' => [Permissions::SYSTEM_TENANTS_VIEW]]);
        $this->actingAs($admin, 'admin')->get('/system/error-log')->assertForbidden();
    }

    public function test_authorized_account_sees_empty_state(): void
    {
        $admin = $this->admin(['role' => 'admin', 'permissions' => [Permissions::SYSTEM_DEVELOPER_ACCESS]]);
        $this->actingAs($admin, 'admin')->get('/system/error-log')->assertOk()
            ->assertInertia(fn ($page) => $page->component('System/ErrorLog/Index')->has('entries', 0));
    }

    public function test_records_are_filtered_bounded_and_sensitive_context_is_omitted(): void
    {
        $admin = $this->admin(['role' => 'super_admin']);
        $lines = [];

        foreach (['error', 'warning', 'error'] as $level) {
            $lines[] = json_encode(['timestamp' => '2026-11-09T00:00:00Z', 'level' => $level,
                'message' => 'Failure password=do-not-show user@example.test Bearer private-token',
                'context' => ['token' => 'context-secret', 'request' => ['password' => 'private']]]);
        }
        File::put($this->directory.'/detailed-2026-11-09.log', implode("\n", $lines)."\ninvalid-json\n");
        $response = $this->actingAs($admin, 'admin')->get('/system/error-log?level=error&tail=1');
        $response->assertOk()->assertInertia(fn ($page) => $page->has('entries', 1)
            ->where('entries.0.level', 'error')->where('entries.0.message', 'Failure password=[redacted] [redacted] Bearer [redacted]')
            ->missing('entries.0.context')->missing('meta.logPath'));
    }

    public function test_large_file_read_is_capped(): void
    {
        File::put($this->directory.'/detailed.log', str_repeat('x', 1100000)."\n".json_encode(['level' => 'error', 'message' => 'last record'])."\n");
        $this->actingAs($this->admin(['role' => 'super_admin']), 'admin')
            ->get('/system/error-log')->assertOk()->assertInertia(fn ($page) => $page->where('meta.truncated', true)->has('entries', 1));
    }

    public function test_filter_does_not_accept_paths_or_unknown_levels(): void
    {
        $this->actingAs($this->admin(['role' => 'super_admin']), 'admin')
            ->getJson('/system/error-log?tail=999999&level=../../private')->assertUnprocessable();
    }
}

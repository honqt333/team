<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Center;
use App\Models\Customer;
use App\Models\Quote;
use App\Models\TaxPeriod;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\WorkOrder;
use App\Services\QuoteConversionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use InvalidArgumentException;
use Mockery;
use RuntimeException;
use Symfony\Component\Process\Process;
use Tests\TestCase;

class ThirdBatchSafetyTest extends TestCase
{
    use RefreshDatabase;

    private function quote(): Quote
    {
        $tenant = Tenant::factory()->create();
        $center = Center::factory()->create(['tenant_id' => $tenant->id]);
        $customer = Customer::factory()->create(['tenant_id' => $tenant->id, 'center_id' => $center->id]);
        $vehicle = Vehicle::factory()->create(['tenant_id' => $tenant->id, 'center_id' => $center->id, 'customer_id' => $customer->id]);

        return Quote::create(['tenant_id' => $tenant->id, 'center_id' => $center->id, 'customer_id' => $customer->id, 'vehicle_id' => $vehicle->id, 'code' => 'THIRD-'.bin2hex(random_bytes(3)), 'status' => 'sent', 'valid_until' => now()->addDay()]);
    }

    public function test_database_outage_returns_safe_503(): void
    {
        $this->assertDependencyFailure('db');
    }

    public function test_cache_outage_returns_safe_503(): void
    {
        $this->assertDependencyFailure('cache');
    }

    public function test_queue_outage_returns_safe_503(): void
    {
        $this->assertDependencyFailure('queue');
    }

    private function assertDependencyFailure(string $dependency): void
    {
        $error = new RuntimeException('mysql://secret-user:secret-pass@private-host/db token=secret-token');

        if ($dependency === 'db') {
            DB::shouldReceive('connection')->andThrow($error);
        } elseif ($dependency === 'cache') {
            Cache::shouldReceive('put')->andThrow($error);
        } else {
            Queue::shouldReceive('connection')->andThrow($error);
        }
        $response = $this->get('/api/readyz')->assertStatus(503)->assertHeader('X-Correlation-Id');
        $response->assertJsonPath('status', 'degraded')->assertJsonPath('checks.'.$dependency.'.status', 'fail');
        $this->assertStringNotContainsString('secret-', $response->getContent());
        $this->assertStringNotContainsString('private-host', $response->getContent());
    }

    public function test_public_decision_is_terminal_and_only_logged_once(): void
    {
        $quote = $this->quote();
        Log::spy();
        $this->post('/view/quote/'.$quote->uuid.'/approve')->assertSessionHas('success');
        $this->post('/view/quote/'.$quote->uuid.'/reject', ['rejection_reason' => 'Changed mind'])->assertSessionHas('error');
        $this->post('/view/quote/'.$quote->uuid.'/approve')->assertSessionHas('error');
        $this->assertSame('approved', $quote->fresh()->status);
        Log::shouldHaveReceived('notice')->once()->with('quote.approve', Mockery::on(fn ($ctx) => $ctx['quote_id'] === $quote->id && $ctx['tenant_id'] === $quote->tenant_id && $ctx['channel'] === 'public_link'));
        Log::shouldNotHaveReceived('notice', ['quote.reject', Mockery::any()]);
    }

    public function test_rejection_is_terminal_and_expired_or_deleted_quotes_cannot_be_actioned(): void
    {
        $quote = $this->quote();
        $this->post('/view/quote/'.$quote->uuid.'/reject', ['rejection_reason' => 'No'])->assertSessionHas('success');
        $this->post('/view/quote/'.$quote->uuid.'/approve')->assertSessionHas('error');
        $this->assertSame('rejected', $quote->fresh()->status);
        $expired = $this->quote();
        $expired->update(['valid_until' => now()->subDay()]);
        $this->post('/view/quote/'.$expired->uuid.'/reject', ['rejection_reason' => 'No'])->assertSessionHas('error');
        $this->post('/view/quote/'.$expired->uuid.'/approve')->assertSessionHas('error');
        $this->assertSame('sent', $expired->fresh()->status);
        $deleted = $this->quote();
        $deleted->delete();
        $this->post('/view/quote/'.$deleted->uuid.'/approve')->assertNotFound();
    }

    public function test_tenant_scope_gate_rejects_a_violation_and_accepts_a_scoped_model(): void
    {
        $root = sys_get_temp_dir().'/khidmh-scope-'.bin2hex(random_bytes(6));
        mkdir($root.'/scripts', 0700, true);
        mkdir($root.'/app/Models', 0700, true);
        copy(base_path('scripts/check-tenant-scope.sh'), $root.'/scripts/check-tenant-scope.sh');
        $model = $root.'/app/Models/Fixture.php';

        try {
            file_put_contents($model, '<?php class Fixture { public $tenant_id; }');
            $process = new Process(['bash', $root.'/scripts/check-tenant-scope.sh']);
            $process->run();
            $this->assertSame(1, $process->getExitCode());
            $this->assertStringContainsString('violations: 1', $process->getOutput());
            file_put_contents($model, '<?php class Fixture { use TenantScoped; public $tenant_id; }');
            $process->run();
            $this->assertSame(0, $process->getExitCode());
        } finally {
            unlink($model);
            unlink($root.'/scripts/check-tenant-scope.sh');
            rmdir($root.'/scripts');
            rmdir($root.'/app/Models');
            rmdir($root.'/app');
            rmdir($root);
        }
    }

    public function test_legacy_report_is_tenant_specific_and_does_not_modify_records(): void
    {
        $quote = $this->quote();
        $period = TaxPeriod::create(['tenant_id' => $quote->tenant_id, 'center_id' => $quote->center_id, 'period_key' => '2026-Q1', 'name' => 'Legacy', 'year' => 2026, 'year_period' => '1', 'period_type' => 'quarterly', 'start_date' => '2026-01-01', 'end_date' => '2026-03-31', 'status' => 'draft']);
        $before = $period->fresh()->getAttributes();
        $this->artisan('tax-periods:review-legacy', ['tenant' => $quote->tenant_id])->expectsOutputToContain('"legacy_period_count": 1')->assertSuccessful();
        $this->assertSame($before, $period->fresh()->getAttributes());
        $other = Tenant::factory()->create();
        $this->artisan('tax-periods:review-legacy', ['tenant' => $other->id])->expectsOutputToContain('"legacy_period_count": 0')->assertSuccessful();
    }

    public function test_stale_approved_quote_cannot_create_a_second_work_order(): void
    {
        $quote = $this->quote();
        $quote->update(['status' => 'approved']);
        $stale = $quote->fresh();
        $user = User::factory()->create(['tenant_id' => $quote->tenant_id, 'current_center_id' => $quote->center_id]);
        $service = app(QuoteConversionService::class);
        $workOrder = $service->convert($quote, $user);

        try {
            $service->convert($stale, $user);
            $this->fail('Duplicate conversion accepted');
        } catch (InvalidArgumentException $e) {
            $this->assertSame(__('quotes.already_converted'), $e->getMessage());
        }
        $this->assertSame(1, WorkOrder::where('quote_id', $quote->id)->count());
        $this->assertSame($workOrder->id, $quote->fresh()->converted_work_order_id);
    }
}

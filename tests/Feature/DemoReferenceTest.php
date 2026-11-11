<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Tenant;
use Database\Seeders\TestingEnvironmentSeeder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use LogicException;
use Tests\TestCase;

class DemoReferenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_reference_is_repeatable_and_preserves_unrelated_and_existing_records(): void
    {
        $outside = Tenant::factory()->create();
        $this->seed(TestingEnvironmentSeeder::class);

        foreach ([
            'active-workshop' => ['pro', 'active'],
            'new-workshop' => ['trial', 'trialing'],
            'expiring-workshop' => ['basic', 'active'],
            'expired-workshop' => ['basic', 'expired'],
        ] as $slug => [$plan, $status]) {
            $tenant = Tenant::where('slug', $slug)->sole();
            $subscription = $tenant->subscriptions()->sole();
            $this->assertSame($plan, $subscription->plan->slug);
            $this->assertSame($status, $subscription->status);
            $this->assertSame(1, $tenant->centers()->count());
        }

        $active = Tenant::where('slug', 'active-workshop')->sole();
        $active->update(['name' => 'Preserved demo edit']);
        $counts = [];

        foreach (['customers', 'vehicles', 'users', 'hr_employees', 'suppliers', 'parts', 'inventory_balances', 'work_orders', 'invoices', 'payments'] as $table) {
            $counts[$table] = DB::table($table)->where('tenant_id', $active->id)->count();
            $this->assertGreaterThan(0, $counts[$table], $table);
        }
        $this->assertSame(1005, $counts['customers']);
        $invoice = DB::table('invoices')->where('tenant_id', $active->id)->sole();
        $payment = DB::table('payments')->where('invoice_id', $invoice->id)->sole();
        $this->assertSame('paid', $invoice->payment_status);
        $this->assertSame($invoice->work_order_id, $payment->work_order_id);
        $this->assertEquals($invoice->total_incl_tax, $payment->amount);
        $this->seed(TestingEnvironmentSeeder::class);

        foreach ($counts as $table => $count) {
            $this->assertSame($count, DB::table($table)->where('tenant_id', $active->id)->count(), $table);
        }
        $this->assertSame('Preserved demo edit', $active->fresh()->name);
        $this->assertNotNull($outside->fresh());
        $this->assertSame(8, Tenant::count());
        $this->assertSame(0, Tenant::where('slug', 'new-tenant')->sole()->subscriptions()->count());

        foreach (['trial-just-ended' => false, 'trial-ending-soon' => true] as $slug => $future) {
            $trial = Tenant::where('slug', $slug)->sole()->subscriptions()->sole();
            $this->assertSame('trialing', $trial->status);
            $this->assertSame($future, $trial->trial_ends_at->isFuture());
        }
    }

    public function test_a_missing_required_plan_rolls_back_only_the_new_fixture_data(): void
    {
        $outside = Tenant::factory()->create();
        DB::table('plans')->where('slug', 'trial')->update(['slug' => 'renamed-trial']);

        try {
            $this->seed(TestingEnvironmentSeeder::class);
            $this->fail('The reference must not silently substitute a different plan.');
        } catch (ModelNotFoundException) {
            $this->assertDatabaseMissing('tenants', ['slug' => 'active-workshop']);
            $this->assertNotNull($outside->fresh());
            $this->assertDatabaseCount('customers', 0);
        }
    }

    public function test_demo_entry_point_refuses_production_before_writing(): void
    {
        $this->app->detectEnvironment(fn () => 'production');

        try {
            (new TestingEnvironmentSeeder)->run();
            $this->fail('Production must not receive demo records.');
        } catch (LogicException $exception) {
            $this->assertStringContainsString('local or testing', $exception->getMessage());
            $this->assertDatabaseMissing('tenants', ['slug' => 'active-workshop']);
        } finally {
            $this->app->detectEnvironment(fn () => 'testing');
        }
    }
}

<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\Center;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Tenant;
use App\Models\User;
use App\Models\WorkOrder;
use App\Services\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_record_payment_calculates_balance(): void
    {
        $tenant = Tenant::factory()->create();
        $center = Center::factory()->forTenant($tenant)->create();
        $user = User::factory()->create(['tenant_id' => $tenant->id, 'current_center_id' => $center->id]);
        $customer = Customer::factory()->create(['tenant_id' => $tenant->id, 'center_id' => $center->id]);
        $wo = WorkOrder::factory()->create(['tenant_id' => $tenant->id, 'center_id' => $center->id]);
        $invoice = Invoice::factory()->create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'customer_id' => $customer->id,
            'work_order_id' => $wo->id,
            'total_incl_tax' => 1000,
            'payment_status' => 'unpaid',
        ]);

        $service = app(PaymentService::class);
        $payment = $service->recordPayment($invoice, [
            'amount' => 1000,
            'type' => 'payment',
            'payment_method' => 'cash',
        ]);

        $this->assertEquals(1000, $payment->amount);
    }
}

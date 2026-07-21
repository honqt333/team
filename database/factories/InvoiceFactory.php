<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Center;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Tenant;
use App\Models\WorkOrder;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Invoice>
 */
class InvoiceFactory extends Factory
{
    protected $model = Invoice::class;

    public function definition(): array
    {
        return [
            'tenant_id' => fn () => Tenant::factory(),
            'center_id' => fn () => Center::factory(),
            'customer_id' => fn () => Customer::factory(),
            'work_order_id' => fn () => WorkOrder::factory(),
            'invoice_number' => 'INV-'.$this->faker->unique()->numberBetween(1000, 9999),
            'issue_date' => now(),
            'supply_date' => now()->toDateString(),
            'type' => 'invoice',
            'status' => 'draft',
            'payment_status' => 'unpaid',
            'tax_enabled_snapshot' => false,
            'pricing_mode_snapshot' => 'exclusive',
            'tax_rate_snapshot' => 15.00,
            'currency_code' => 'SAR',
            'total_excl_tax' => 0,
            'total_tax' => 0,
            'total_incl_tax' => 0,
            'total_taxable_amount' => 0,
            'total_paid' => 0,
        ];
    }
}

<?php

namespace Database\Factories;

use App\Models\Center;
use App\Models\Customer;
use App\Models\Tenant;
use App\Models\Vehicle;
use App\Models\WorkOrder;
use Illuminate\Database\Eloquent\Factories\Factory;

class WorkOrderFactory extends Factory
{
    protected $model = WorkOrder::class;

    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'center_id' => Center::factory(),
            'customer_id' => Customer::factory(),
            'vehicle_id' => Vehicle::factory(),
            'code' => 'WO-' . $this->faker->unique()->numberBetween(1000, 9999),
            'status' => 'open',
            'tax_enabled_snapshot' => false,
            'pricing_mode_snapshot' => 'exclusive',
            'tax_rate_snapshot' => 15.00,
            'currency_code' => 'SAR',
            'total_excl_tax' => 0,
            'total_tax' => 0,
            'total_incl_tax' => 0,
        ];
    }
}

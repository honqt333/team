<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Center;
use App\Models\ServiceBay;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ServiceBay>
 */
class ServiceBayFactory extends Factory
{
    protected $model = ServiceBay::class;

    public function definition(): array
    {
        $tenant = Tenant::factory()->create();
        $center = Center::factory()->create(['tenant_id' => $tenant->id]);

        return [
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'name' => 'Bay '.$this->faker->numberBetween(1, 99),
            'code' => 'B'.$this->faker->numberBetween(1, 99),
            'is_active' => true,
            'sort_order' => 0,
        ];
    }
}

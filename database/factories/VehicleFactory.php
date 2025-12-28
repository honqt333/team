<?php

namespace Database\Factories;

use App\Models\Center;
use App\Models\Customer;
use App\Models\Tenant;
use App\Models\Vehicle;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Vehicle>
 */
class VehicleFactory extends Factory
{
    protected $model = Vehicle::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'center_id' => Center::factory(),
            'customer_id' => Customer::factory(),
            'plate_number' => strtoupper(fake()->bothify('??? ####')),
            'year' => fake()->numberBetween(2010, 2025),
            'vin' => fake()->optional()->regexify('[A-HJ-NPR-Z0-9]{17}'),
            'odometer' => fake()->optional()->numberBetween(0, 300000),
            'notes' => fake()->optional()->sentence(),
        ];
    }

    /**
     * Configure the vehicle for a specific tenant and center.
     */
    public function forTenant(Tenant $tenant, Center $center): static
    {
        return $this->state(fn(array $attributes) => [
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
        ]);
    }

    /**
     * Configure the vehicle for a specific customer.
     */
    public function forCustomer(Customer $customer): static
    {
        return $this->state(fn(array $attributes) => [
            'tenant_id' => $customer->tenant_id,
            'center_id' => $customer->center_id,
            'customer_id' => $customer->id,
        ]);
    }
}

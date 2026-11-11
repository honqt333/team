<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Center;
use App\Models\Customer;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Customer>
 */
class CustomerFactory extends Factory
{
    protected $model = Customer::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'center_id' => fn (array $attributes) => Center::factory()->create([
                'tenant_id' => $attributes['tenant_id'],
            ])->id,
            'type' => 'individual',
            'name' => fake()->name(),
            'contact_name' => null,
            'phone' => fake()->phoneNumber(),
            'email' => fake()->optional()->safeEmail(),
            'notes' => fake()->optional()->sentence(),
        ];
    }

    /**
     * Indicate that the customer is a company.
     */
    public function company(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'company',
            'name' => fake()->company(),
            'contact_name' => fake()->name(),
        ]);
    }
}

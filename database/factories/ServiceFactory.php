<?php

namespace Database\Factories;

use App\Models\Center;
use App\Models\Service;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Service>
 */
class ServiceFactory extends Factory
{
    protected $model = Service::class;

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
            'name_ar' => fake('ar_SA')->words(3, true),
            'name_en' => fake()->words(3, true),
            'description_ar' => fake('ar_SA')->optional()->sentence(),
            'description_en' => fake()->optional()->sentence(),
            'base_price' => fake()->randomFloat(2, 50, 500),
            'min_price' => fake()->randomFloat(2, 30, 100),
            'default_discount_type' => 'none',
            'default_discount_value' => 0,
            'allow_price_override' => true,
            'type' => 'internal',
            'is_active' => true,
            'sort_order' => 0,
        ];
    }

    /**
     * Configure the service for a specific tenant and center.
     */
    public function forTenant(Tenant $tenant, Center $center): static
    {
        return $this->state(fn(array $attributes) => [
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
        ]);
    }

    /**
     * Configure the service as inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn(array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Configure the service as external.
     */
    public function external(): static
    {
        return $this->state(fn(array $attributes) => [
            'type' => 'external',
        ]);
    }
}

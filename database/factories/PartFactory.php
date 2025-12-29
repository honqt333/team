<?php

namespace Database\Factories;

use App\Models\Part;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

class PartFactory extends Factory
{
    protected $model = Part::class;

    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'sku' => strtoupper($this->faker->unique()->bothify('???-####')),
            'name_ar' => $this->faker->words(3, true),
            'name_en' => $this->faker->words(3, true),
            'unit' => $this->faker->randomElement(['piece', 'liter', 'kg', 'meter', 'box']),
            'category' => $this->faker->randomElement(['زيوت', 'فلاتر', 'فرامل', 'كهرباء', 'تعليق']),
            'description' => $this->faker->optional()->sentence(),
            'min_qty' => $this->faker->randomFloat(2, 0, 10),
            'reorder_qty' => $this->faker->randomFloat(2, 5, 20),
            'default_sale_price' => $this->faker->randomFloat(2, 10, 500),
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}

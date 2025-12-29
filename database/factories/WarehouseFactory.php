<?php

namespace Database\Factories;

use App\Models\Center;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Factories\Factory;

class WarehouseFactory extends Factory
{
    protected $model = Warehouse::class;

    public function definition(): array
    {
        return [
            'center_id' => Center::factory(),
            'name' => $this->faker->randomElement(['المستودع الرئيسي', 'مستودع القطع', 'مستودع الزيوت']),
            'code' => strtoupper($this->faker->unique()->bothify('WH-##')),
            'is_default' => false,
            'is_active' => true,
        ];
    }

    public function default(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_default' => true,
        ]);
    }
}

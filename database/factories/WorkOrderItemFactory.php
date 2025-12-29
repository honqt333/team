<?php

namespace Database\Factories;

use App\Models\Service;
use App\Models\WorkOrder;
use App\Models\WorkOrderItem;
use Illuminate\Database\Eloquent\Factories\Factory;

class WorkOrderItemFactory extends Factory
{
    protected $model = WorkOrderItem::class;

    public function definition(): array
    {
        $unitPrice = $this->faker->randomFloat(2, 50, 500);
        
        return [
            'work_order_id' => WorkOrder::factory(),
            'service_id' => Service::factory(),
            'tenant_id' => fn (array $attrs) => WorkOrder::find($attrs['work_order_id'])?->tenant_id ?? 1,
            'center_id' => fn (array $attrs) => WorkOrder::find($attrs['work_order_id'])?->center_id ?? 1,
            'title' => $this->faker->sentence(3),
            'qty' => 1,
            'unit_price' => $unitPrice,
            'base_price_snapshot' => $unitPrice,
            'min_price_snapshot' => $unitPrice * 0.8,
            'discount_type' => 'none',
            'discount_amount' => 0,
            'final_unit_price' => $unitPrice,
            'line_total' => $unitPrice,
            'total' => $unitPrice,
            'price_locked' => false,
            'status' => 'pending',
        ];
    }
}

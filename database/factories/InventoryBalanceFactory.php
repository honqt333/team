<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Center;
use App\Models\InventoryBalance;
use App\Models\Part;
use App\Models\Tenant;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InventoryBalance>
 */
class InventoryBalanceFactory extends Factory
{
    protected $model = InventoryBalance::class;

    public function definition(): array
    {
        return [
            'tenant_id' => fn () => Tenant::factory(),
            'center_id' => fn () => Center::factory(),
            'warehouse_id' => fn () => Warehouse::factory(),
            'part_id' => fn () => Part::factory(),
            'qty_on_hand' => 0,
            'wac_cost' => 0,
            'sale_price' => 0,
            'min_sale_price' => 0,
            'min_stock' => 0,
            'allow_price_change' => false,
            'is_active' => true,
        ];
    }
}

<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Center;
use App\Models\Customer;
use App\Models\Department;
use App\Models\Part;
use App\Models\Service;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Lightweight fixture seeder for performance baseline tests.
 *
 * Deliberately small dataset so the test suite completes in < 60s on SQLite.
 * The important thing is that relative query counts demonstrate N+1 fixes,
 * not absolute wall-clock timing (which varies wildly across environments).
 */
class PerformanceBaselineSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $now = now()->toDateTimeString();

            // 1 tenant + 1 center
            $tenant = Tenant::factory()->create();
            $center = Center::factory()->create(['tenant_id' => $tenant->id]);
            $centerId = $center->id;

            // 1 super-admin user, attached to the center
            $user = User::factory()->create([
                'tenant_id' => $tenant->id,
                'current_center_id' => $centerId,
                'is_system_admin' => true,
            ]);

            DB::table('center_user')->insert([
                'tenant_id' => $tenant->id,
                'user_id' => $user->id,
                'center_id' => $centerId,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            // 3 departments
            for ($d = 1; $d <= 3; $d++) {
                Department::create([
                    'tenant_id' => $tenant->id,
                    'center_id' => $centerId,
                    'name_ar' => "قسم {$d}",
                    'name_en' => "Department {$d}",
                    'is_active' => true,
                    'sort_order' => $d,
                ]);
            }

            // 10 services
            Service::factory()->count(10)->create([
                'tenant_id' => $tenant->id,
                'center_id' => $centerId,
            ]);

            // 1 warehouse + 20 parts + 1 vehicle make
            $warehouse = Warehouse::factory()->create([
                'tenant_id' => $tenant->id,
                'center_id' => $centerId,
                'is_default' => true,
            ]);
            $parts = Part::factory()->count(20)->create(['tenant_id' => $tenant->id]);

            $makeId = DB::table('vehicle_makes')->insertGetId([
                'name_ar' => 'تويوتا',
                'name_en' => 'Toyota',
                'is_active' => true,
                'sort_order' => 1,
                'source' => 'center',
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            // 30 customers × 2 vehicles = 60 vehicles
            $custRows = [];
            $vehRows = [];

            for ($cu = 1; $cu <= 30; $cu++) {
                $custRows[] = [
                    'tenant_id' => $tenant->id,
                    'center_id' => $centerId,
                    'name' => "Customer {$cu}",
                    'phone' => '050'.str_pad((string) $cu, 7, '0', STR_PAD_LEFT),
                    'type' => 'individual',
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
            DB::table('customers')->insert($custRows);

            $customersResult = DB::table('customers')
                ->where('tenant_id', $tenant->id)
                ->orderBy('id')
                ->pluck('id')
                ->values();

            $vehId = 1;

            foreach ($customersResult as $custId) {
                for ($v = 0; $v < 2; $v++) {
                    $vehRows[] = [
                        'tenant_id' => $tenant->id,
                        'center_id' => $centerId,
                        'customer_id' => $custId,
                        'make_id' => $makeId,
                        'plate_number' => 'PF-'.str_pad((string) $vehId, 4, '0', STR_PAD_LEFT),
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                    $vehId++;

                }
            }
            DB::table('vehicles')->insert($vehRows);

            // 20 inventory balances (1 per part)
            $balRows = [];

            foreach ($parts as $part) {
                $balRows[] = [
                    'warehouse_id' => $warehouse->id,
                    'part_id' => $part->id,
                    'qty_on_hand' => 50,
                    'wac_cost' => 30.00,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
            DB::table('inventory_balances')->insert($balRows);

            // Fetch actual inserted customer IDs for WO creation
            $custIds = DB::table('customers')
                ->where('tenant_id', $tenant->id)
                ->pluck('id')
                ->values()
                ->all();
            $vehIds = DB::table('vehicles')
                ->where('tenant_id', $tenant->id)
                ->pluck('id')
                ->values()
                ->all();

            // 50 work orders (spread across statuses)
            $statuses = array_merge(
                array_fill(0, 30, 'in_progress'),
                array_fill(0, 10, 'done'),
                array_fill(0, 5, 'on_hold'),
                array_fill(0, 5, 'cancelled'),
            );

            $woRows = [];
            $itemRows = [];

            foreach ($statuses as $i => $status) {
                $woId = $i + 1;
                $woRows[] = [
                    'id' => $woId,
                    'tenant_id' => $tenant->id,
                    'center_id' => $centerId,
                    'customer_id' => $custIds[$i % count($custIds)],
                    'vehicle_id' => $vehIds[$i % count($vehIds)],
                    'code' => 'WO-'.str_pad((string) $woId, 6, '0', STR_PAD_LEFT),
                    'status' => $status,
                    'tax_enabled_snapshot' => false,
                    'pricing_mode_snapshot' => 'exclusive',
                    'tax_rate_snapshot' => 15.00,
                    'currency_code' => 'SAR',
                    'total_excl_tax' => 100.00,
                    'total_tax' => 15.00,
                    'total_incl_tax' => 115.00,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];

                // 2 items per work order
                for ($j = 0; $j < 2; $j++) {
                    $itemRows[] = [
                        'tenant_id' => $tenant->id,
                        'center_id' => $centerId,
                        'work_order_id' => $woId,
                        'title' => "Service Item {$j}",
                        'unit_price' => 50.00,
                        'qty' => 1,
                        'total' => 50.00,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }
            }

            DB::table('work_orders')->insert($woRows);
            DB::table('work_order_items')->insert($itemRows);
        });
    }
}

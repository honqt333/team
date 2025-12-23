<?php

namespace Database\Seeders;

use App\Models\VehicleMake;
use App\Models\VehicleModel;
use Illuminate\Database\Seeder;

class VehicleMakesSeeder extends Seeder
{
    public function run(): void
    {
        $makes = [
            'Toyota' => ['Camry', 'Corolla', 'Land Cruiser', 'Hilux', 'Prado', 'Yaris', 'RAV4', 'Fortuner'],
            'Honda' => ['Accord', 'Civic', 'CR-V', 'Pilot', 'HR-V'],
            'Hyundai' => ['Sonata', 'Elantra', 'Tucson', 'Santa Fe', 'Accent', 'Creta'],
            'Nissan' => ['Altima', 'Sentra', 'Patrol', 'X-Trail', 'Sunny', 'Pathfinder'],
            'Ford' => ['F-150', 'Explorer', 'Expedition', 'Mustang', 'Edge', 'Taurus'],
            'Chevrolet' => ['Tahoe', 'Suburban', 'Silverado', 'Malibu', 'Traverse', 'Camaro'],
            'BMW' => ['3 Series', '5 Series', '7 Series', 'X3', 'X5', 'X7'],
            'Mercedes-Benz' => ['C-Class', 'E-Class', 'S-Class', 'GLC', 'GLE', 'GLS'],
            'Kia' => ['Optima', 'Cerato', 'Sportage', 'Sorento', 'Carnival'],
            'GMC' => ['Sierra', 'Yukon', 'Terrain', 'Acadia'],
            'Lexus' => ['ES', 'LS', 'RX', 'LX', 'GX', 'NX'],
            'Mazda' => ['Mazda3', 'Mazda6', 'CX-5', 'CX-9'],
            'Mitsubishi' => ['Pajero', 'Montero', 'Outlander', 'L200', 'ASX'],
            'Jeep' => ['Wrangler', 'Grand Cherokee', 'Cherokee', 'Compass'],
            'Dodge' => ['Charger', 'Challenger', 'Durango', 'Ram'],
        ];

        foreach ($makes as $makeName => $models) {
            $make = VehicleMake::firstOrCreate(['name' => $makeName]);
            
            foreach ($models as $modelName) {
                VehicleModel::firstOrCreate([
                    'make_id' => $make->id,
                    'name' => $modelName,
                ]);
            }
        }
    }
}

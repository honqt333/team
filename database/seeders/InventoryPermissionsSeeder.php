<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class InventoryPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            // Base
            'inventory.view',
            'inventory.settings.manage',

            // Parts
            'inventory.parts.view',
            'inventory.parts.create',
            'inventory.parts.update',
            'inventory.parts.deactivate',
            
            // Stock
            'inventory.stock.view',
            
            // Ledger
            'inventory.ledger.view',
            
            // Receipts
            'inventory.receipts.view',
            'inventory.receipts.create',
            'inventory.receipts.post',
            'inventory.receipts.cancel',
            
            // Adjustments
            'inventory.adjustments.view',
            'inventory.adjustments.create',
            'inventory.adjustments.post',
            'inventory.adjustments.cancel',
            
            // Issue
            'inventory.issue.view',
            'inventory.issue.create',
            'inventory.issue.reverse',

            // Moves
            'inventory.moves.view',
            'inventory.moves.create',
            
            // Transfers (Sprint 4)
            'inventory.transfers.view',
            'inventory.transfers.create',
            'inventory.transfers.send',
            'inventory.transfers.receive',
            'inventory.transfers.cancel',
            
            // Override
            'inventory.override_negative_stock',
            
            // Purchasing (Sprint 2)
            'purchasing.suppliers.view',
            'purchasing.suppliers.create',
            'purchasing.suppliers.update',
            'purchasing.suppliers.deactivate',
            'purchasing.pos.view',
            'purchasing.pos.create',
            'purchasing.pos.update',
            'purchasing.pos.send',
            'purchasing.pos.cancel',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        $this->command->info('Inventory & Purchasing permissions seeded successfully.');
    }
}

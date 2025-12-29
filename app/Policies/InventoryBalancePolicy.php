<?php

namespace App\Policies;

use App\Models\InventoryBalance;
use App\Models\User;

class InventoryBalancePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('inventory.stock.view');
    }

    public function view(User $user, InventoryBalance $balance): bool
    {
        return $user->can('inventory.stock.view');
    }
}

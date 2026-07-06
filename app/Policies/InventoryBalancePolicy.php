<?php
namespace App\Policies;

use App\Models\InventoryBalance;
use App\Models\User;

use App\Support\Permissions;
class InventoryBalancePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(Permissions::INVENTORY_STOCK_VIEW);
    }

    public function view(User $user, InventoryBalance $balance): bool
    {
        return $user->can(Permissions::INVENTORY_STOCK_VIEW);
    }
}

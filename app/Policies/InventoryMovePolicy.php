<?php

namespace App\Policies;

use App\Models\InventoryMove;
use App\Models\User;

class InventoryMovePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('inventory.moves.view');
    }

    public function view(User $user, InventoryMove $move): bool
    {
        return $user->can('inventory.moves.view');
    }

    public function create(User $user, string $type = 'receipt'): bool
    {
        return match ($type) {
            'receipt' => $user->can('inventory.receipts.create'),
            'adjustment' => $user->can('inventory.adjustments.create'),
            'issue' => $user->can('inventory.issue.create'),
            default => false,
        };
    }

    public function reverse(User $user, InventoryMove $move): bool
    {
        // Check appropriate permission based on move type
        return match ($move->move_type) {
            InventoryMove::TYPE_RECEIPT => $user->can('inventory.receipts.cancel'),
            InventoryMove::TYPE_ADJUSTMENT_IN,
            InventoryMove::TYPE_ADJUSTMENT_OUT => $user->can('inventory.adjustments.cancel'),
            InventoryMove::TYPE_ISSUE_TO_WORKORDER => $user->can('inventory.issue.reverse'),
            default => false,
        };
    }
}

<?php

namespace App\Policies;

use App\Models\InventoryMove;
use App\Models\User;
use App\Support\Permissions;

class InventoryMovePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(Permissions::INVENTORY_MOVES_VIEW);
    }

    public function view(User $user, InventoryMove $move): bool
    {
        return $user->can(Permissions::INVENTORY_MOVES_VIEW);
    }

    public function create(User $user, string $type = 'receipt'): bool
    {
        // The Permissions registry only defines a single
        // inventory.moves.create permission. The legacy
        // implementation split this per type
        // (receipt/adjustment/issue); until those permissions
        // are added to the registry, the single create
        // permission governs all of them.
        $canMove = $user->can(Permissions::INVENTORY_MOVES_CREATE);

        return match ($type) {
            'receipt', 'adjustment', 'issue' => $canMove,
            default => false,
        };
    }

    public function reverse(User $user, InventoryMove $move): bool
    {
        // There is no dedicated "reverse" permission in the
        // registry; the create permission is the closest
        // signal for whether a user is allowed to touch the
        // inventory ledger at all.
        return $user->can(Permissions::INVENTORY_MOVES_CREATE);
    }
}

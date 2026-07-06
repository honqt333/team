<?php

namespace App\Policies;

use App\Models\GoodsReceivedNote;
use App\Models\User;
use App\Support\Permissions;

class GoodsReceivedNotePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(Permissions::POS_VIEW);
    }

    public function view(User $user, GoodsReceivedNote $grn): bool
    {
        return $user->can(Permissions::POS_VIEW);
    }

    public function create(User $user): bool
    {
        // Can receive goods against a PO if the user can view POs.
        return $user->can(Permissions::POS_VIEW);
    }

    public function post(User $user, GoodsReceivedNote $grn): bool
    {
        // No dedicated "post" permission exists; the legacy code
        // referenced an undefined 'inventory.receipts.post' string.
        // INVENTORY_MOVES_CREATE is the closest available gate for
        // posting inventory movements.
        return $user->can(Permissions::INVENTORY_MOVES_CREATE)
            && $grn->canBePosted();
    }

    public function cancel(User $user, GoodsReceivedNote $grn): bool
    {
        return $user->can(Permissions::INVENTORY_MOVES_CREATE)
            && $grn->canBeCancelled();
    }
}

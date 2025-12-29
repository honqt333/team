<?php

namespace App\Policies;

use App\Models\GoodsReceivedNote;
use App\Models\User;

class GoodsReceivedNotePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('purchasing.pos.view');
    }

    public function view(User $user, GoodsReceivedNote $grn): bool
    {
        return $user->can('purchasing.pos.view');
    }

    public function create(User $user): bool
    {
        return $user->can('purchasing.pos.view'); // Can receive if can view PO
    }

    public function post(User $user, GoodsReceivedNote $grn): bool
    {
        return $user->can('inventory.receipts.post') && $grn->canBePosted();
    }

    public function cancel(User $user, GoodsReceivedNote $grn): bool
    {
        return $user->can('inventory.receipts.cancel') && $grn->canBeCancelled();
    }
}

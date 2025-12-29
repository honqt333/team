<?php

namespace App\Policies;

use App\Models\InventoryTransfer;
use App\Models\User;

class InventoryTransferPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('inventory.transfers.view');
    }

    public function view(User $user, InventoryTransfer $transfer): bool
    {
        return $user->can('inventory.transfers.view') 
            && $user->tenant_id === $transfer->tenant_id;
    }

    public function create(User $user): bool
    {
        return $user->can('inventory.transfers.create');
    }

    public function update(User $user, InventoryTransfer $transfer): bool
    {
        return $user->can('inventory.transfers.create') 
            && $user->tenant_id === $transfer->tenant_id
            && $transfer->canBeModified();
    }

    public function send(User $user, InventoryTransfer $transfer): bool
    {
        return $user->can('inventory.transfers.send') 
            && $user->tenant_id === $transfer->tenant_id
            && $transfer->canBeSent();
    }

    public function receive(User $user, InventoryTransfer $transfer): bool
    {
        return $user->can('inventory.transfers.receive') 
            && $user->tenant_id === $transfer->tenant_id
            && $transfer->canBeReceived();
    }

    public function cancel(User $user, InventoryTransfer $transfer): bool
    {
        return $user->can('inventory.transfers.cancel') 
            && $user->tenant_id === $transfer->tenant_id
            && $transfer->canBeCancelled();
    }
}

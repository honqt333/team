<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\InventoryTransfer;
use App\Models\User;
use App\Support\Permissions;

class InventoryTransferPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(Permissions::INVENTORY_MOVES_VIEW);
    }

    public function view(User $user, InventoryTransfer $transfer): bool
    {
        return $user->can(Permissions::INVENTORY_MOVES_VIEW)
            && $user->tenant_id === $transfer->tenant_id;
    }

    public function create(User $user): bool
    {
        return $user->can(Permissions::INVENTORY_MOVES_CREATE);
    }

    public function update(User $user, InventoryTransfer $transfer): bool
    {
        return $user->can(Permissions::INVENTORY_MOVES_CREATE)
            && $user->tenant_id === $transfer->tenant_id
            && $transfer->canBeModified();
    }

    public function send(User $user, InventoryTransfer $transfer): bool
    {
        return $user->can(Permissions::INVENTORY_MOVES_CREATE)
            && $user->tenant_id === $transfer->tenant_id
            && $transfer->canBeSent();
    }

    public function receive(User $user, InventoryTransfer $transfer): bool
    {
        return $user->can(Permissions::INVENTORY_MOVES_CREATE)
            && $user->tenant_id === $transfer->tenant_id
            && $transfer->canBeReceived();
    }

    public function cancel(User $user, InventoryTransfer $transfer): bool
    {
        return $user->can(Permissions::INVENTORY_MOVES_CREATE)
            && $user->tenant_id === $transfer->tenant_id
            && $transfer->canBeCancelled();
    }
}

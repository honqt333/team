<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\PurchaseOrder;
use App\Models\User;
use App\Support\Permissions;

class PurchaseOrderPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(Permissions::POS_VIEW);
    }

    public function view(User $user, PurchaseOrder $order): bool
    {
        return $user->can(Permissions::POS_VIEW)
            && $user->tenant_id === $order->tenant_id;
    }

    public function create(User $user): bool
    {
        return $user->can(Permissions::POS_CREATE);
    }

    public function update(User $user, PurchaseOrder $order): bool
    {
        return $user->can(Permissions::POS_UPDATE)
            && $user->tenant_id === $order->tenant_id
            && $order->isDraft();
    }

    public function send(User $user, PurchaseOrder $order): bool
    {
        return $user->can(Permissions::POS_SEND)
            && $user->tenant_id === $order->tenant_id
            && $order->canBeSent();
    }

    public function cancel(User $user, PurchaseOrder $order): bool
    {
        return $user->can(Permissions::POS_CANCEL)
            && $user->tenant_id === $order->tenant_id
            && $order->canBeCancelled();
    }
}

<?php

namespace App\Policies;

use App\Models\PurchaseOrder;
use App\Models\User;

class PurchaseOrderPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('purchasing.pos.view');
    }

    public function view(User $user, PurchaseOrder $order): bool
    {
        return $user->can('purchasing.pos.view') 
            && $user->tenant_id === $order->tenant_id;
    }

    public function create(User $user): bool
    {
        return $user->can('purchasing.pos.create');
    }

    public function update(User $user, PurchaseOrder $order): bool
    {
        return $user->can('purchasing.pos.update') 
            && $user->tenant_id === $order->tenant_id
            && $order->isDraft();
    }

    public function send(User $user, PurchaseOrder $order): bool
    {
        return $user->can('purchasing.pos.send') 
            && $user->tenant_id === $order->tenant_id
            && $order->canBeSent();
    }

    public function cancel(User $user, PurchaseOrder $order): bool
    {
        return $user->can('purchasing.pos.cancel') 
            && $user->tenant_id === $order->tenant_id
            && $order->canBeCancelled();
    }
}

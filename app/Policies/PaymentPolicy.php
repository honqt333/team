<?php

namespace App\Policies;

use App\Models\Payment;
use App\Models\User;
use App\Support\Permissions;

class PaymentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(Permissions::PAYMENTS_VIEW);
    }

    public function view(User $user, Payment $payment): bool
    {
        if (! $user->can(Permissions::PAYMENTS_VIEW)) {
            return false;
        }

        return $payment->tenant_id === $user->tenant_id
            && $payment->center_id === $user->current_center_id;
    }

    public function create(User $user): bool
    {
        return $user->can(Permissions::PAYMENTS_CREATE);
    }

    public function update(User $user, Payment $payment): bool
    {
        if (! $user->can(Permissions::PAYMENTS_UPDATE)) {
            return false;
        }

        return $payment->tenant_id === $user->tenant_id
            && $payment->center_id === $user->current_center_id;
    }

    public function delete(User $user, Payment $payment): bool
    {
        if (! $user->can(Permissions::PAYMENTS_DELETE)) {
            return false;
        }

        return $payment->tenant_id === $user->tenant_id
            && $payment->center_id === $user->current_center_id;
    }

    public function refund(User $user, Payment $payment): bool
    {
        return $this->update($user, $payment);
    }
}

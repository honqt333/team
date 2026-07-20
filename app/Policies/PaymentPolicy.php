<?php

namespace App\Policies;

use App\Models\Payment;
use App\Models\User;

class PaymentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('payments.view');
    }

    public function view(User $user, Payment $payment): bool
    {
        if (! $user->can('payments.view')) {
            return false;
        }

        return $payment->tenant_id === $user->tenant_id
            && $payment->center_id === $user->current_center_id;
    }

    public function create(User $user): bool
    {
        return $user->can('payments.create');
    }

    public function update(User $user, Payment $payment): bool
    {
        if (! $user->can('payments.update')) {
            return false;
        }

        return $payment->tenant_id === $user->tenant_id
            && $payment->center_id === $user->current_center_id;
    }

    public function delete(User $user, Payment $payment): bool
    {
        if (! $user->can('payments.delete')) {
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

<?php

namespace App\Policies;

use App\Models\Invoice;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class InvoicePolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('invoices.view');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Invoice $invoice): bool
    {
        return $user->hasPermissionTo('invoices.view')
            && $user->tenant_id === $invoice->tenant_id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('invoices.create');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Invoice $invoice): bool
    {
        return $user->hasPermissionTo('invoices.edit')
            && $user->tenant_id === $invoice->tenant_id
            && $invoice->status === 'draft'; // Can only update drafts
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Invoice $invoice): bool
    {
        return $user->hasPermissionTo('invoices.delete')
            && $user->tenant_id === $invoice->tenant_id
            && $invoice->status === 'draft'; // Can only delete drafts
    }

    /**
     * Determine whether the user can record payments.
     */
    public function recordPayment(User $user, Invoice $invoice): bool
    {
        return $user->hasPermissionTo('payments.create')
            && $user->tenant_id === $invoice->tenant_id
            && $invoice->payment_status !== 'paid';
    }
}
